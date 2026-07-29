<?php

namespace Tests\Unit;

use App\Services\PlaceImport\CsvPlaceReader;
use App\Services\PlaceImport\PlaceImportOutputValidator;
use App\Services\PlaceImport\PlaceImportPrompt;
use InvalidArgumentException;
use Tests\TestCase;

class PlaceImportTest extends TestCase
{
    public function test_csv_reader_separates_import_and_ai_payloads(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'place-import-');
        file_put_contents($path, "title,category,address,link,latitude,longitude,open_hours,place_id,phone,website,price_range,descriptions,thumbnail,about,images\n".
            "Cafe,Cà phê,\"12, Hang Bai\",https://maps.example/1,21.0285,105.8542,\"{\\\"Thứ Hai\\\":[\\\"08:00–22:00\\\"]}\",google-1,0901,https://example.test,100.000,Mo ta,https://image.test/1.jpg,\"{\\\"service\\\":true}\",ignored\n");

        $records = iterator_to_array((new CsvPlaceReader)->read($path));
        unlink($path);

        $this->assertCount(1, $records);
        $this->assertSame([
            'name',
            'address_text',
            'google_place_id',
            'phone',
            'website_url',
            'google_maps_url',
            'latitude',
            'longitude',
            'description',
            'thumbnail_url',
        ], array_keys($records[0]['import_data']));
        $this->assertSame([
            'record_ref',
            'title',
            'address_text',
            'google_maps_url',
            'latitude',
            'longitude',
            'price_range',
            'open_hours',
            'descriptions',
            'about',
        ], array_keys($records[0]['ai_data']));
        $this->assertSame(['Thứ Hai' => ['08:00–22:00']], $records[0]['ai_data']['open_hours']);
        $this->assertSame(['service' => true], $records[0]['ai_data']['about']);
        $this->assertArrayNotHasKey('thumbnail', $records[0]['ai_data']);
        $this->assertArrayNotHasKey('images', $records[0]);
    }

    public function test_csv_reader_skips_records_missing_google_place_id(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'place-import-');
        file_put_contents($path, "title,address,link,latitude,longitude,place_id\nCafe,Hanoi,https://maps.example/1,21.0285,105.8542,\n");
        $reader = new CsvPlaceReader;

        $records = iterator_to_array($reader->read($path));
        unlink($path);

        $this->assertSame([], $records);
        $this->assertSame(1, $reader->skippedRows());
    }

    public function test_validator_accepts_ids_and_splits_cross_midnight_hours(): void
    {
        $result = (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'hanoi_Z001.csv:2']],
            $this->taxonomy(),
            [
                'results' => [[
                    'record_ref' => 'hanoi_Z001.csv:2',
                    'error' => false,
                    'error_reason' => null,
                    'normalized_address' => 'Hà Nội',
                    'category_id' => 10,
                    'tag_ids' => [20],
                    'district_id' => 30,
                    'min_price_vnd' => 200000,
                    'max_price_vnd' => 300000,
                    'opening_hours' => [[
                        'day_of_week' => 8,
                        'schedule_type' => 'regular',
                        'opens_at' => '18:00',
                        'closes_at' => '02:00',
                    ]],
                ]],
            ],
        );

        $this->assertSame([
            ['day_of_week' => 8, 'schedule_type' => 'regular', 'opens_at' => '18:00', 'closes_at' => '23:59'],
            ['day_of_week' => 2, 'schedule_type' => 'regular', 'opens_at' => '00:00', 'closes_at' => '02:00'],
        ], $result['hanoi_Z001.csv:2']['opening_hours']);
        $this->assertSame(200000, $result['hanoi_Z001.csv:2']['min_price_vnd']);
        $this->assertSame(300000, $result['hanoi_Z001.csv:2']['max_price_vnd']);
        $this->assertFalse($result['hanoi_Z001.csv:2']['error']);
    }

    public function test_validator_downgrades_invalid_record_without_rejecting_valid_batch_record(): void
    {
        $result = (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'record-1'], ['record_ref' => 'record-2']],
            $this->taxonomy(),
            [
                'results' => [
                    [
                        'record_ref' => 'record-1',
                        'error' => false,
                        'normalized_address' => 'Hà Nội',
                        'category_id' => 10,
                        'tag_ids' => [999],
                        'district_id' => 30,
                        'min_price_vnd' => null,
                        'max_price_vnd' => null,
                        'opening_hours' => [],
                    ],
                    [
                        'record_ref' => 'record-2',
                        'error' => false,
                        'normalized_address' => 'Hà Nội',
                        'category_id' => 10,
                        'tag_ids' => [],
                        'district_id' => 30,
                        'min_price_vnd' => null,
                        'max_price_vnd' => null,
                        'opening_hours' => [],
                    ],
                ],
            ],
        );

        $this->assertSame([
            'error' => true,
            'error_reason' => 'unknown_tag',
            'normalized_address' => null,
            'category_id' => null,
            'tag_ids' => [],
            'district_id' => null,
            'min_price_vnd' => null,
            'max_price_vnd' => null,
            'opening_hours' => [],
        ], $result['record-1']);
        $this->assertFalse($result['record-2']['error']);
    }

    public function test_validator_rejects_batch_with_mismatched_record_refs(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'record-1']],
            $this->taxonomy(),
            ['results' => [['record_ref' => 'another-record', 'error' => true]]],
        );
    }

    public function test_prompt_uses_id_contract_and_does_not_contain_local_import_fields(): void
    {
        $prompt = (new PlaceImportPrompt)->build(
            [[
                'record_ref' => 'record-1',
                'title' => 'Cafe',
                'address_text' => 'Hà Nội',
                'google_maps_url' => 'https://maps.example/1',
                'latitude' => 21.0285,
                'longitude' => 105.8542,
                'price_range' => '200-300 N ₫',
                'open_hours' => [],
                'descriptions' => 'Mô tả',
                'about' => ['service' => true],
            ]],
            $this->taxonomy(),
        );

        $this->assertStringContainsString('category_id', $prompt);
        $this->assertStringContainsString('tag_ids', $prompt);
        $this->assertStringContainsString('district_id', $prompt);
        $this->assertStringContainsString('"about"', $prompt);
        $this->assertStringContainsString('"address_text"', $prompt);
        $this->assertStringContainsString('"latitude"', $prompt);
        $this->assertStringContainsString('"longitude"', $prompt);
        $this->assertStringContainsString('"price_range"', $prompt);
        $this->assertStringContainsString('min_price_vnd=200000', $prompt);
        $this->assertStringContainsString('max_price_vnd=300000', $prompt);
        $this->assertStringNotContainsString('thumbnail_url', $prompt);
        $this->assertStringNotContainsString('google_place_id', $prompt);
        $this->assertStringNotContainsString('"category"', $prompt);
    }

    public function test_validator_rejects_invalid_ai_price_range(): void
    {
        $result = (new PlaceImportOutputValidator)->validate(
            [['record_ref' => 'record-1']],
            $this->taxonomy(),
            [
                'results' => [[
                    'record_ref' => 'record-1',
                    'error' => false,
                    'normalized_address' => 'Hà Nội',
                    'category_id' => 10,
                    'tag_ids' => [],
                    'district_id' => 30,
                    'min_price_vnd' => 300000,
                    'max_price_vnd' => 200000,
                    'opening_hours' => [],
                ]],
            ],
        );

        $this->assertTrue($result['record-1']['error']);
        $this->assertSame('invalid_price_range', $result['record-1']['error_reason']);
    }

    /**
     * @return array<string, mixed>
     */
    private function taxonomy(): array
    {
        return [
            'categories' => [[
                'id' => 10,
                'slug' => 'an-uong',
                'name' => 'Ăn uống',
            ]],
            'districts' => [['id' => 30, 'name' => 'Ba Đình']],
            'tags' => [['id' => 20, 'slug' => 'chill', 'name' => 'Chill']],
        ];
    }
}
