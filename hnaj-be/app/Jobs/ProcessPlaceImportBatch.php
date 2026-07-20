<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Place;
use App\Models\PlaceExternalId;
use App\Models\PlaceImportBatch;
use App\Services\PlaceClassifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ProcessPlaceImportBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    private const STALE_ROW_AFTER_SECONDS = 300;

    private const MAX_ROW_ATTEMPTS = 3;

    public function __construct(public readonly string $batchId) {}

    public function handle(PlaceClassifier $classifier): void
    {
        $batch = PlaceImportBatch::query()->find($this->batchId);
        if ($batch === null || in_array($batch->status, ['paused', 'completed', 'completed_with_errors'], true)) {
            return;
        }
        if ($batch->status === 'pause_requested') {
            $batch->update(['status' => 'paused', 'paused_at' => now()]);

            return;
        }

        $batch->update(['status' => 'processing', 'started_at' => $batch->started_at ?? now()]);
        $rows = DB::transaction(function () use ($batch) {
            $staleBefore = now()->subSeconds(self::STALE_ROW_AFTER_SECONDS);
            $rows = $batch->rows()
                ->where(function ($query) use ($staleBefore): void {
                    $query->where('status', 'pending')
                        ->orWhere(function ($query) use ($staleBefore): void {
                            $query->where('status', 'processing')
                                ->where('updated_at', '<=', $staleBefore);
                        });
                })
                ->where('attempts', '<', self::MAX_ROW_ATTEMPTS)
                ->orderBy('row_number')
                ->limit(10)
                ->lock('for update')
                ->get();
            if ($rows->isNotEmpty()) {
                $rows->each(fn ($row) => $row->update(['status' => 'processing', 'attempts' => $row->attempts + 1]));
            }

            return $rows;
        });
        if ($rows->isEmpty()) {
            $this->finish($batch);

            return;
        }

        try {
            $items = $classifier->classify($rows);
            $byId = collect($items)->keyBy('row_id');
            DB::transaction(function () use ($batch, $rows, $byId): void {
                foreach ($rows as $row) {
                    $item = $byId->get($row->id);
                    if ($item === null) {
                        throw new \RuntimeException('AI thiếu classification cho một row.');
                    }
                    $this->importRow($row, $item);
                }
                $this->refreshProgress($batch);
            });
        } catch (Throwable $exception) {
            $rows->each(fn ($row) => $row->update([
                'status' => 'failed', 'errors' => [$exception->getMessage()], 'processed_at' => now(),
            ]));
            $this->refreshProgress($batch);
        }

        $batch->refresh();
        if ($batch->status === 'pause_requested') {
            $batch->update(['status' => 'paused', 'paused_at' => now()]);
        } elseif ($batch->rows()->where(function ($query): void {
            $query->where('status', 'pending')
                ->orWhere(function ($query): void {
                    $query->where('status', 'processing')
                        ->where('updated_at', '<=', now()->subSeconds(self::STALE_ROW_AFTER_SECONDS))
                        ->where('attempts', '<', self::MAX_ROW_ATTEMPTS);
                });
        })->exists()) {
            self::dispatch($batch->id)->onQueue('imports');
        } else {
            $this->finish($batch);
        }
    }

    private function importRow($row, array $classification): void
    {
        $data = $row->normalized_data;
        $place = Place::create([
            'name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.Str::lower(Str::substr($row->id, 0, 8)),
            'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
            'price_min' => $classification['price_min'], 'price_max' => $classification['price_max'],
            'category_id' => $classification['category_id'], 'district_id' => $classification['district_id'],
            'ward_id' => $classification['ward_id'], 'address' => $data['address'],
            'website' => $data['website'] ?? null, 'phone' => $data['phone'] ?? null,
            'opening_hours' => $data['opening_hours'] ?? null, 'source_category' => $data['source_category'] ?? null,
            'cover_image' => $data['cover_image'] ?? null, 'gallery' => $data['gallery'] ?? null,
            'status' => 'published',
        ]);
        $place->tags()->sync($classification['tag_ids']);
        if ($row->external_id !== null) {
            PlaceExternalId::create(['place_id' => $place->id, 'provider' => 'google', 'external_id' => $row->external_id, 'fingerprint' => $row->fingerprint]);
        }
        $row->update(['classification' => $classification, 'status' => 'imported', 'processed_at' => now()]);
    }

    private function refreshProgress(PlaceImportBatch $batch): void
    {
        $counts = $batch->rows()->selectRaw("sum(status = 'imported') imported, sum(status = 'skipped') skipped, sum(status = 'failed') failed, sum(status in ('imported','skipped','failed','invalid')) processed")->first();
        $processed = (int) ($counts->processed ?? 0);
        $batch->update(['processed_rows' => $processed, 'imported_rows' => (int) $counts->imported, 'skipped_rows' => (int) $counts->skipped, 'failed_rows' => (int) $counts->failed, 'progress_percent' => (int) round($processed * 100 / max(1, $batch->total_rows)), 'progress_updated_at' => now()]);
    }

    private function finish(PlaceImportBatch $batch): void
    {
        $this->refreshProgress($batch);
        $batch->refresh();
        $batch->update(['status' => $batch->failed_rows > 0 ? 'completed_with_errors' : 'completed', 'completed_at' => now()]);
    }
}
