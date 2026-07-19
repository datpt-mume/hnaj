<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\AdministrativeArea;
use App\Models\Place;
use App\Models\PlaceImportBatch;
use App\Models\PlaceImportRow;
use App\Models\Tag;
use App\Services\PlaceAiClassifier;
use App\Services\PlaceClassifier;
use App\Services\PlaceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

final class PlaceImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_the_runtime_classifier_binding(): void
    {
        self::assertInstanceOf(PlaceAiClassifier::class, $this->app->make(PlaceClassifier::class));
        self::assertInstanceOf(PlaceImportService::class, $this->app->make(PlaceImportService::class));
    }

    public function test_it_commits_classified_rows_atomically(): void
    {
        $category = Category::create(['name' => 'Ăn uống', 'slug' => 'an-uong']);
        $tag = Tag::create(['name' => 'Lẩu', 'slug' => 'lau', 'group_name' => 'Ẩm thực']);
        $district = AdministrativeArea::create(['name' => 'Cầu Giấy', 'slug' => 'cau-giay', 'type' => 'district']);
        $ward = AdministrativeArea::create(['name' => 'Yên Hòa', 'slug' => 'yen-hoa', 'type' => 'ward', 'parent_id' => $district->id]);
        $batch = PlaceImportBatch::create(['filename' => 'x.csv', 'status' => 'previewed']);
        $row = PlaceImportRow::create([
            'batch_id' => $batch->id, 'row_number' => 1, 'external_id' => 'g-1', 'fingerprint' => 'fp-1',
            'status' => 'pending', 'normalized_data' => ['name' => 'Quán Lẩu', 'address' => 'Hanoi', 'latitude' => 21, 'longitude' => 105],
        ]);
        $classifier = Mockery::mock(PlaceClassifier::class);
        $classifier->shouldReceive('classify')->once()->andReturn([['row_id' => $row->id, 'category_id' => $category->id, 'tag_ids' => [$tag->id], 'district_id' => $district->id, 'ward_id' => $ward->id]]);

        $result = (new PlaceImportService($classifier))->confirm($batch);

        self::assertSame('completed', $result->status);
        self::assertSame(1, Place::count());
        self::assertSame(1, Place::first()->tags()->count());
    }

    public function test_ai_failure_cancels_batch_without_creating_places(): void
    {
        $batch = PlaceImportBatch::create(['filename' => 'x.csv', 'status' => 'previewed']);
        PlaceImportRow::create(['batch_id' => $batch->id, 'row_number' => 1, 'status' => 'pending', 'normalized_data' => ['name' => 'Quán', 'address' => 'Hanoi', 'latitude' => 21, 'longitude' => 105]]);
        $classifier = Mockery::mock(PlaceClassifier::class);
        $classifier->shouldReceive('classify')->once()->andThrow(new \RuntimeException('AI failed'));

        $result = (new PlaceImportService($classifier))->confirm($batch);

        self::assertSame('cancelled', $result->status);
        self::assertSame(0, Place::count());
    }
}
