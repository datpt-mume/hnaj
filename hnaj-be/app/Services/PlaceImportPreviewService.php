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
            ]);

            foreach ($parsed['rows'] as $entry) {
                $row = $entry['data'];
                $duplicate = $this->isDuplicate($row['external_id'], $row['fingerprint']);
                PlaceImportRow::create([
                    'batch_id' => $batch->id,
                    'row_number' => $entry['row_number'],
                    'external_id' => $row['external_id'],
                    'fingerprint' => $row['fingerprint'],
                    'status' => $duplicate ? 'duplicate' : 'pending',
                    'normalized_data' => $row,
                    'errors' => $duplicate ? ['Địa điểm đã tồn tại'] : null,
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
                'duplicate_rows' => $batch->rows()->where('status', 'duplicate')->count(),
                'valid_rows' => $batch->rows()->where('status', 'pending')->count(),
            ]);

            return $batch->load('rows');
        });
    }

    private function isDuplicate(?string $externalId, string $fingerprint): bool
    {
        return ($externalId !== null && PlaceExternalId::query()->where('provider', 'google')->where('external_id', $externalId)->exists())
            || PlaceExternalId::query()->where('fingerprint', $fingerprint)->exists();
    }
}
