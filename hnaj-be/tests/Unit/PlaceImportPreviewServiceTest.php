<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Place;
use App\Models\PlaceExternalId;
use App\Services\PlaceCsvParser;
use App\Services\PlaceImportPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaceImportPreviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stages_valid_rows_and_marks_existing_external_ids_as_duplicates(): void
    {
        PlaceExternalId::create([
            'place_id' => Place::create([
                'name' => 'Existing', 'slug' => 'existing', 'latitude' => 21, 'longitude' => 105,
                'price_min' => 50000, 'price_max' => 100000, 'address' => 'Hanoi',
            ])->id,
            'provider' => 'google',
            'external_id' => 'google-1',
            'fingerprint' => 'existing-fingerprint',
        ]);

        $path = tempnam(sys_get_temp_dir(), 'hnaj-csv-');
        file_put_contents($path, "title,address,latitude,longitude,cid\nNew place,Hanoi,21,105,google-1\n");

        $batch = (new PlaceImportPreviewService(new PlaceCsvParser))->preview($path, 'places.csv');
        unlink($path);

        self::assertSame('previewed', $batch->status);
        self::assertSame('duplicate', $batch->rows->first()->status);
        self::assertSame(1, $batch->duplicate_rows);
        self::assertSame(0, Place::query()->where('name', 'New place')->count());
    }

    public function test_it_preserves_source_row_numbers_for_valid_and_invalid_rows(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hnaj-csv-');
        file_put_contents($path, "title,address,latitude,longitude\nValid place,Hanoi,21,105\nMalformed,Hanoi,21,105,unexpected\nAnother place,Hanoi,21.1,105.1\n");

        $batch = (new PlaceImportPreviewService(new PlaceCsvParser))->preview($path, 'places.csv');
        unlink($path);

        self::assertSame([2, 3, 4], $batch->rows->pluck('row_number')->sort()->values()->all());
        self::assertSame(3, $batch->total_rows);
        self::assertSame(2, $batch->valid_rows);
        self::assertSame(1, $batch->invalid_rows);
    }
}
