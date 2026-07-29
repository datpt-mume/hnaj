<?php

namespace App\Services\PlaceImport;

use Generator;
use SplFileObject;

class CsvPlaceReader
{
    private int $skippedRows = 0;

    public function skippedRows(): int
    {
        return $this->skippedRows;
    }

    /**
     * @return Generator<int, array<string, mixed>>
     */
    public function read(string $path): Generator
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $file->setCsvControl(',');

        $headers = null;

        foreach ($file as $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(
                    static fn (mixed $header): string => ltrim(trim((string) $header), "\xEF\xBB\xBF"),
                    $row,
                );

                continue;
            }

            $values = array_pad($row, count($headers), null);
            $record = array_combine($headers, array_slice($values, 0, count($headers)));

            if ($record === false) {
                $this->skippedRows++;

                continue;
            }

            $normalized = $this->normalize($record, $file->key() + 1, $path);

            if ($normalized !== null) {
                yield $normalized;
            } else {
                $this->skippedRows++;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function normalize(array $row, int $line, string $path): ?array
    {
        $name = $this->stringValue($row['title'] ?? null);
        $address = $this->stringValue($row['address'] ?? null);
        $mapsUrl = $this->stringValue($row['link'] ?? null);
        $latitude = $this->decimalValue($row['latitude'] ?? null);
        $longitude = $this->decimalValue($row['longitude'] ?? null);
        $googlePlaceId = $this->stringValue($row['place_id'] ?? null);

        if ($name === null || $address === null || $mapsUrl === null || $latitude === null || $longitude === null || $googlePlaceId === null) {
            return null;
        }

        $recordRef = sprintf('%s:%d', basename($path), $line);

        return [
            'record_ref' => $recordRef,
            'import_data' => [
                'name' => $name,
                'address_text' => $address,
                'google_place_id' => $googlePlaceId,
                'phone' => $this->stringValue($row['phone'] ?? null),
                'website_url' => $this->stringValue($row['website'] ?? null),
                'google_maps_url' => $mapsUrl,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'price_range' => $this->stringValue($row['price_range'] ?? null),
                'description' => $this->stringValue($row['descriptions'] ?? null),
                'thumbnail_url' => $this->stringValue($row['thumbnail'] ?? null),
            ],
            'ai_data' => [
                'record_ref' => $recordRef,
                'title' => $name,
                'address_text' => $address,
                'google_maps_url' => $mapsUrl,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'open_hours' => $this->parseJsonValue($row['open_hours'] ?? null),
                'descriptions' => $this->stringValue($row['descriptions'] ?? null),
                'about' => $this->parseJsonValue($row['about'] ?? null),
            ],
        ];
    }

    private function stringValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function decimalValue(mixed $value): ?float
    {
        $value = trim((string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function parseJsonValue(mixed $value): array|string|null
    {
        $value = $this->stringValue($value);

        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE && str_contains($value, '\\"')) {
            $decoded = json_decode(stripslashes($value), true);
        }

        return is_array($decoded) ? $decoded : $value;
    }
}
