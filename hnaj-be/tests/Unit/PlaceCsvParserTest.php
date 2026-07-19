<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PlaceCsvParser;
use PHPUnit\Framework\TestCase;

final class PlaceCsvParserTest extends TestCase
{
    public function test_it_streams_and_normalizes_safe_place_fields(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hnaj-csv-');
        file_put_contents($path, "\xEF\xBB\xBFtitle,address,latitude,longitude,cid,price_range,open_hours,reviews,emails\n".
            "Quán\tX,12 Phố Huế,21.02,105.85,google-1,50.000 - 100.000 VND,\"{\"\"Thứ Hai\"\":[\"08:00–22:00\"]}\",secret review,owner@example.com\n");

        $result = (new PlaceCsvParser)->parse($path);
        unlink($path);

        self::assertCount(1, $result['rows']);
        self::assertSame(2, $result['rows'][0]['row_number']);
        self::assertSame('google-1', $result['rows'][0]['data']['external_id']);
        self::assertSame(50000, $result['rows'][0]['data']['price_min']);
        self::assertSame(100000, $result['rows'][0]['data']['price_max']);
        self::assertArrayNotHasKey('reviews', $result['rows'][0]['data']);
        self::assertArrayNotHasKey('emails', $result['rows'][0]['data']);
        self::assertNotEmpty($result['rows'][0]['data']['fingerprint']);
    }

    public function test_it_reports_invalid_required_fields(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hnaj-csv-');
        file_put_contents($path, "title,address,latitude,longitude\n,,,\n");

        $result = (new PlaceCsvParser)->parse($path);
        unlink($path);

        self::assertCount(0, $result['rows']);
        self::assertCount(1, $result['errors']);
        self::assertCount(4, $result['errors'][0]['errors']);
    }

    public function test_it_reports_rows_with_more_values_than_headers(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hnaj-csv-');
        file_put_contents($path, "title,address,latitude,longitude\nQuán X,12 Phố Huế,21.02,105.85,unexpected\n");

        $result = (new PlaceCsvParser)->parse($path);
        unlink($path);

        self::assertCount(0, $result['rows']);
        self::assertSame([
            ['row' => 2, 'errors' => ['Số cột không khớp header']],
        ], $result['errors']);
    }
}
