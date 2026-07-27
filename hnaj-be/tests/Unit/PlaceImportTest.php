<?php

namespace Tests\Unit;

use App\Services\PlaceImport\CsvPlaceReader;
use App\Services\PlaceImport\PlaceDuplicateDetector;
use App\Services\PlaceImport\PlaceImportOutputValidator;
use App\Services\PlaceImport\PlaceImportPersistence;
use App\Services\PlaceImport\PlaceImportPrompt;
use InvalidArgumentException;
use Tests\TestCase;

class PlaceImportTest extends TestCase
{
    public function test_csv_reader_preserves_quoted_json_and_maps_required_fields(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'place-import-');
        file_put_contents($path, "title,address,link,latitude,longitude,open_hours,place_id\n".
            "Cafe,\"12, Hang Bai\",https://maps.example/1,21.0285,105.8542,\"{\\\"Thứ Hai\\\":[\\\"08:00–22:00\\\"]}\",google-1\n");

        $records = iterator_to_array((new CsvPlaceReader)->read($path));
        unlink($path);

        $this->assertCount(1, $records);
        $this->assertSame('Cafe', $records[0]['name']);
        $this->assertSame('12, Hang Bai', $records[0]['address_text']);
        $this->assertSame(['Thứ Hai' => ['08:00–22:00']], $records[0]['opening_hours_source']);
    }

    public function test_validator_accepts_taxonomy_and_splits_cross_midnight_hours(): void
    {
        $records = [['record_ref' => 'hanoi_Z001.csv:2']];
        $taxonomy = [
            'categories' => ['an-uong' => ['allowed_tag_slugs' => ['chill']]],
            'district_names' => ['Ba Đình'],
            'tag_slugs' => ['chill'],
        ];

        $result = (new PlaceImportOutputValidator)->validate($records, $taxonomy, [
            'results' => [[
                'record_ref' => 'hanoi_Z001.csv:2',
                'accepted' => true,
                'category_slug' => 'an-uong',
                'tag_slugs' => ['chill'],
                'district_name' => 'Ba Đình',
                'opening_hours' => [[
                    'day_of_week' => 8,
                    'schedule_type' => 'regular',
                    'opens_at' => '18:00',
                    'closes_at' => '02:00',
                ]],
            ]],
        ]);

        $this->assertSame([
            ['day_of_week' => 8, 'schedule_type' => 'regular', 'opens_at' => '18:00', 'closes_at' => '23:59'],
            ['day_of_week' => 2, 'schedule_type' => 'regular', 'opens_at' => '00:00', 'closes_at' => '02:00'],
        ], $result['hanoi_Z001.csv:2']['opening_hours']);
    }

    public function test_prompt_contains_strict_day_mapping_and_taxonomy(): void
    {
        $prompt = (new PlaceImportPrompt)->build(
            [['record_ref' => 'record-1', 'name' => 'Cafe']],
            [
                'categories' => ['an-uong' => ['allowed_tag_slugs' => ['chill']]],
                'district_names' => ['Ba Đình'],
                'tag_slugs' => ['chill'],
            ],
        );

        $this->assertStringContainsString('2=Monday through 7=Saturday and 8=Sunday', $prompt);
        $this->assertStringContainsString('an-uong', $prompt);
        $this->assertStringContainsString('record-1', $prompt);
    }

    public function test_duplicate_detector_coordinate_key_ignores_name(): void
    {
        $detector = new PlaceDuplicateDetector;
        $method = new \ReflectionMethod($detector, 'coordinateKey');
        $method->setAccessible(true);

        $this->assertSame(
            $method->invoke($detector, ['name' => 'Cafe', 'latitude' => 21.0285, 'longitude' => 105.8542]),
            $method->invoke($detector, ['name' => 'Another Cafe', 'latitude' => 21.0285, 'longitude' => 105.8542]),
        );
        $this->assertNotSame(
            $method->invoke($detector, ['name' => 'Cafe', 'latitude' => 21.0285, 'longitude' => 105.8542]),
            $method->invoke($detector, ['name' => 'Cafe', 'latitude' => 21.0286, 'longitude' => 105.8542]),
        );
    }

    public function test_price_parser_preserves_thousands_separator(): void
    {
        $persistence = new PlaceImportPersistence;
        $method = new \ReflectionMethod($persistence, 'price');
        $method->setAccessible(true);

        $this->assertSame([1, 100000], $method->invoke($persistence, '1-100.000 ₫'));
        $this->assertSame([200, 300], $method->invoke($persistence, '200-300 N ₫'));
    }

    public function test_validator_rejects_non_string_tag_slug(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'record-1']],
            [
                'categories' => ['an-uong' => ['allowed_tag_slugs' => ['chill']]],
                'district_names' => ['Ba Đình'],
                'tag_slugs' => ['1'],
            ],
            [
                'results' => [[
                    'record_ref' => 'record-1',
                    'accepted' => true,
                    'category_slug' => 'an-uong',
                    'tag_slugs' => [1],
                    'district_name' => 'Ba Đình',
                    'opening_hours' => [],
                ]],
            ],
        );
    }

    public function test_validator_rejects_tag_not_allowed_by_category(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'record-1']],
            [
                'categories' => ['an-uong' => ['allowed_tag_slugs' => []]],
                'district_names' => ['Ba Đình'],
                'tag_slugs' => ['chill'],
            ],
            [
                'results' => [[
                    'record_ref' => 'record-1',
                    'accepted' => true,
                    'category_slug' => 'an-uong',
                    'tag_slugs' => ['chill'],
                    'district_name' => 'Ba Đình',
                    'opening_hours' => [],
                ]],
            ],
        );
    }
}
