<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\PlaceStatus;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\Tag;
use App\Services\PlaceImport\CsvPlaceReader;
use App\Services\PlaceImport\OpenAiCompatibleClient;
use App\Services\PlaceImport\PlaceDuplicateDetector;
use App\Services\PlaceImport\PlaceImportOutputValidator;
use App\Services\PlaceImport\PlaceImportPersistence;
use App\Services\PlaceImport\TaxonomyProvider;
use Database\Seeders\PlaceCsvImportSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class PlaceCsvImportSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_filters_duplicates_batches_records_and_imports_only_valid_ai_results(): void
    {
        [$category, $district, $tag] = $this->createTaxonomy();
        $this->createPlace('existing-active', $category, $district);
        $this->createPlace('existing-trashed', $category, $district)->delete();
        $csvPath = $this->createCsvFile();
        $batchSizes = [];

        config()->set([
            'services.place_import_ai.base_url' => 'https://ai.example.test/v1',
            'services.place_import_ai.api_key' => 'test-key',
        ]);

        Http::fake(function (Request $request) use (&$batchSizes, $category, $district, $tag) {
            $prompt = json_decode($request->data()['messages'][1]['content'], true, flags: JSON_THROW_ON_ERROR);
            $batchSizes[] = count($prompt['records']);
            $results = [];

            foreach ($prompt['records'] as $record) {
                $number = (int) str_replace('Place ', '', $record['title']);
                $result = [
                    'record_ref' => $record['record_ref'],
                    'error' => false,
                    'error_reason' => null,
                    'category_id' => $category->id,
                    'tag_ids' => [$tag->id],
                    'district_id' => $district->id,
                    'opening_hours' => [],
                ];

                if ($number === 1) {
                    $result = [
                        'record_ref' => $record['record_ref'],
                        'error' => true,
                        'error_reason' => 'uncertain_category',
                        'category_id' => null,
                        'tag_ids' => [],
                        'district_id' => null,
                        'opening_hours' => [],
                    ];
                } elseif ($number === 2) {
                    $result['tag_ids'] = [999999];
                }

                $results[] = $result;
            }

            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode(['results' => $results], JSON_THROW_ON_ERROR),
                    ],
                ]],
            ]);
        });

        try {
            $this->makeSeeder([$csvPath])->run();
        } finally {
            unlink($csvPath);
        }

        $this->assertSame([10, 2], $batchSizes);
        $this->assertDatabaseMissing('places', ['google_place_id' => 'google-1']);
        $this->assertDatabaseMissing('places', ['google_place_id' => 'google-2']);
        $this->assertDatabaseHas('places', ['google_place_id' => 'google-3']);
        $this->assertSame(10, Place::query()->whereIn('google_place_id', array_map(
            static fn (int $number): string => 'google-'.$number,
            range(1, 12),
        ))->count());
        $this->assertSame(10, PlaceImage::query()->count());
        $this->assertSame(10, Place::query()->whereNotNull('thumbnail_image_id')->where('google_place_id', 'like', 'google-%')->count());

        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $prompt = $request->data()['messages'][1]['content'];

            return str_contains($prompt, '"about"')
                && ! str_contains($prompt, 'thumbnail_url')
                && ! str_contains($prompt, 'google_place_id')
                && ! str_contains($prompt, 'latitude');
        });
    }

    public function test_seeder_fails_before_ai_request_when_taxonomy_is_incomplete(): void
    {
        Http::fake();

        $this->expectException(RuntimeException::class);

        try {
            $this->makeSeeder([])->run();
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_persistence_rolls_back_place_when_related_data_fails(): void
    {
        [$category, $district] = $this->createTaxonomy();
        $persistence = new PlaceImportPersistence;

        try {
            $persistence->import(
                $this->importData('transaction-rollback'),
                [
                    'category_id' => $category->id,
                    'tag_ids' => [],
                    'district_id' => $district->id,
                    'opening_hours' => [[
                        'day_of_week' => 2,
                        'schedule_type' => 'not-a-schedule-type',
                        'opens_at' => null,
                        'closes_at' => null,
                    ]],
                ],
            );

            $this->fail('Expected persistence to fail for an invalid schedule type.');
        } catch (Throwable) {
            $this->assertDatabaseMissing('places', ['google_place_id' => 'transaction-rollback']);
        }
    }

    /**
     * @return array{Category, District, Tag}
     */
    private function createTaxonomy(): array
    {
        $category = Category::query()->create([
            'name' => 'Ăn uống',
            'slug' => 'an-uong',
            'status' => CategoryStatus::Active,
        ]);
        $district = District::query()->create([
            'name' => 'Ba Đình',
            'code' => null,
            'status' => DistrictStatus::Active,
        ]);
        $tag = Tag::query()->create([
            'name' => 'Chill',
            'slug' => 'chill',
            'status' => TagStatus::Active,
        ]);
        $category->tags()->attach($tag->id);

        return [$category, $district, $tag];
    }

    private function createPlace(string $googlePlaceId, Category $category, District $district): Place
    {
        return Place::query()->create([
            ...$this->importData($googlePlaceId),
            'district_id' => $district->id,
            'category_id' => $category->id,
            'min_price' => null,
            'max_price' => null,
            'status' => PlaceStatus::Active,
            'created_by' => null,
            'thumbnail_image_id' => null,
        ]);
    }

    private function createCsvFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'place-import-seeder-');
        $file = fopen($path, 'wb');
        fputcsv($file, [
            'title', 'category', 'address', 'link', 'latitude', 'longitude', 'open_hours', 'place_id',
            'phone', 'website', 'price_range', 'descriptions', 'thumbnail', 'about',
        ]);

        for ($number = 1; $number <= 12; $number++) {
            $this->writeCsvRecord($file, $number, 'google-'.$number);
        }

        $this->writeCsvRecord($file, 99, 'google-1');
        $this->writeCsvRecord($file, 100, 'existing-active');
        $this->writeCsvRecord($file, 101, 'existing-trashed');
        fclose($file);

        return $path;
    }

    /**
     * @param  resource  $file
     */
    private function writeCsvRecord($file, int $number, string $googlePlaceId): void
    {
        fputcsv($file, [
            'Place '.$number,
            'Cà phê',
            $number.' Đường Test, Hà Nội',
            'https://maps.example.test/'.$number,
            21.0000000 + ($number / 100000),
            105.0000000 + ($number / 100000),
            json_encode(['Thứ Hai' => ['08:00–22:00']], JSON_UNESCAPED_UNICODE),
            $googlePlaceId,
            '0900000000',
            'https://example.test/'.$number,
            '50.000-100.000 ₫',
            'Mô tả '.$number,
            'https://images.example.test/'.$number.'.jpg',
            json_encode(['service' => true]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function importData(string $googlePlaceId): array
    {
        return [
            'name' => 'Existing place',
            'address_text' => 'Hà Nội',
            'google_place_id' => $googlePlaceId,
            'phone' => null,
            'website_url' => null,
            'google_maps_url' => 'https://maps.example.test/'.$googlePlaceId,
            'latitude' => 21.0,
            'longitude' => 105.0,
            'price_range' => null,
            'description' => null,
            'thumbnail_url' => null,
        ];
    }

    /**
     * @param  array<int, string>  $files
     */
    private function makeSeeder(array $files): PlaceCsvImportSeeder
    {
        return new class($files, app(CsvPlaceReader::class), app(PlaceDuplicateDetector::class), app(TaxonomyProvider::class), app(OpenAiCompatibleClient::class), app(PlaceImportOutputValidator::class), app(PlaceImportPersistence::class)) extends PlaceCsvImportSeeder
        {
            /**
             * @param  array<int, string>  $files
             */
            public function __construct(
                private readonly array $files,
                CsvPlaceReader $reader,
                PlaceDuplicateDetector $duplicates,
                TaxonomyProvider $taxonomyProvider,
                OpenAiCompatibleClient $aiClient,
                PlaceImportOutputValidator $validator,
                PlaceImportPersistence $persistence,
            ) {
                parent::__construct($reader, $duplicates, $taxonomyProvider, $aiClient, $validator, $persistence);
            }

            protected function csvFiles(): array
            {
                return $this->files;
            }
        };
    }
}
