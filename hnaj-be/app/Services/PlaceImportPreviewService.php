<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlaceExternalId;
use App\Models\PlaceImportBatch;
use App\Models\PlaceImportRow;
use Illuminate\Support\Facades\DB;

final class PlaceImportPreviewService
{
    public function __construct(private readonly PlaceCsvParser $parser) {}

    public function preview(string $path, string $filename): PlaceImportBatch
    {
        $parsed = $this->parser->parse($path);

        return DB::transaction(function () use ($parsed, $filename): PlaceImportBatch {
            $batch = PlaceImportBatch::create([
                'filename' => $filename,
                'status' => 'previewed',
                'total_rows' => count($parsed['rows']) + count($parsed['errors']),
                'valid_rows' => count($parsed['rows']),
                'invalid_rows' => count($parsed['errors']),
                'processed_rows' => count($parsed['errors']),
                'progress_percent' => count($parsed['rows']) + count($parsed['errors']) === 0 ? 100 : 0,
            ]);

            foreach ($parsed['rows'] as $entry) {
                $row = $entry['data'];
                $duplicate = $this->isDuplicate($row['external_id']);
                PlaceImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $entry['row_number'],
                    'external_id' => $row['external_id'],
                    'fingerprint' => $row['fingerprint'],
                    'status' => $duplicate ? 'skipped' : 'pending',
                    'normalized_data' => $row,
                    'errors' => $duplicate ? ['Địa điểm đã tồn tại'] : null,
                    'processed_at' => $duplicate ? now() : null,
                ]);
            }

            foreach ($parsed['errors'] as $error) {
                PlaceImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $error['row'],
                    'status' => 'invalid',
                    'errors' => $error['errors'],
                ]);
            }

            $batch->update([
                'duplicate_rows' => $batch->rows()->where('status', 'skipped')->count(),
                'skipped_rows' => $batch->rows()->where('status', 'skipped')->count(),
                'valid_rows' => $batch->rows()->where('status', 'pending')->count(),
                'processed_rows' => $batch->rows()->whereIn('status', ['skipped', 'invalid'])->count(),
                'progress_percent' => (int) round($batch->rows()->whereIn('status', ['skipped', 'invalid'])->count() * 100 / max(1, $batch->total_rows)),
            ]);

            return $batch->load('rows');
        });
    }

    private function isDuplicate(?string $externalId): bool
    {
        return $externalId !== null
            && PlaceExternalId::query()->where('provider', 'google')->where('external_id', $externalId)->exists();
    }
}
