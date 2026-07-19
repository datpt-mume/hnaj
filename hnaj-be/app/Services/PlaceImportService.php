<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use App\Models\PlaceExternalId;
use App\Models\PlaceImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class PlaceImportService
{
    public function __construct(private readonly PlaceClassifier $classifier) {}

    public function confirm(PlaceImportBatch $batch): PlaceImportBatch
    {
        if ($batch->status !== 'previewed') {
            throw new \RuntimeException('Chỉ batch previewed mới được confirm.');
        }

        $batch->update(['status' => 'classifying', 'confirmed_at' => now()]);

        try {
            $rows = $batch->rows()->where('status', 'pending')->get();
            foreach ($rows->chunk(10) as $chunk) {
                $items = $this->classifier->classify($chunk);
                $byId = collect($items)->keyBy('row_id');
                foreach ($chunk as $row) {
                    $item = $byId->get($row->id);
                    if ($item === null) {
                        throw new \RuntimeException('AI thiếu classification cho một row.');
                    }
                    $row->update(['classification' => $item, 'status' => 'classified']);
                }
            }

            DB::transaction(function () use ($batch): void {
                $batch->load('rows');
                foreach ($batch->rows->where('status', 'classified') as $row) {
                    $data = $row->normalized_data;
                    $place = Place::create([
                        'name' => $data['name'],
                        'slug' => $this->uniqueSlug($data['name'], $row->id),
                        'latitude' => $data['latitude'],
                        'longitude' => $data['longitude'],
                        'price_min' => $data['price_min'] ?? 0,
                        'price_max' => $data['price_max'] ?? $data['price_min'] ?? 0,
                        'category_id' => $row->classification['category_id'],
                        'district_id' => $row->classification['district_id'],
                        'ward_id' => $row->classification['ward_id'],
                        'address' => $data['address'],
                        'website' => $data['website'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'opening_hours' => $data['opening_hours'] ?? null,
                        'source_category' => $data['source_category'] ?? null,
                        'status' => 'published',
                    ]);
                    $place->tags()->sync($row->classification['tag_ids']);
                    if ($row->external_id !== null) {
                        PlaceExternalId::create([
                            'place_id' => $place->id,
                            'provider' => 'google',
                            'external_id' => $row->external_id,
                            'fingerprint' => $row->fingerprint,
                        ]);
                    }
                    $row->update(['status' => 'imported']);
                }
                $batch->update(['status' => 'completed', 'completed_at' => now()]);
            });
        } catch (Throwable $exception) {
            $batch->update(['status' => 'cancelled', 'error_message' => $exception->getMessage()]);
        }

        return $batch->fresh('rows');
    }

    private function uniqueSlug(string $name, string $rowId): string
    {
        return Str::slug($name).'-'.Str::lower(Str::substr($rowId, 0, 8));
    }
}
