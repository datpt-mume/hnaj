<?php

declare(strict_types=1);

namespace App\Services;

use SplFileObject;

final class PlaceCsvParser
{
    /**
    * @return array{rows: list<array{row_number: int, data: array<string, mixed>}>, errors: list<array{row: int, errors: list<string>}>}
     */
    public function parse(string $path): array
    {
        $file = new SplFileObject($path, 'rb');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $headers = $file->fgetcsv();
        if ($headers === false) {
            return ['rows' => [], 'errors' => []];
        }

        $headers = array_map(static fn ($header): string => ltrim((string) $header, "\xEF\xBB\xBF"), $headers);
        $rows = [];
        $errors = [];
        $rowNumber = 1;

        while (! $file->eof()) {
            $values = $file->fgetcsv();
            $rowNumber++;
            if ($values === false || $values === [null]) {
                continue;
            }

            if (count($values) !== count($headers)) {
                $errors[] = ['row' => $rowNumber, 'errors' => ['Số cột không khớp header']];

                continue;
            }

            $raw = array_combine($headers, $values);

            $normalized = $this->normalize($raw);
            if ($normalized['errors'] !== []) {
                $errors[] = ['row' => $rowNumber, 'errors' => $normalized['errors']];

                continue;
            }

            $rows[] = ['row_number' => $rowNumber, 'data' => $normalized['row']];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /** @return array{row: array<string, mixed>, errors: list<string>} */
    private function normalize(array $raw): array
    {
        $name = $this->text($raw['title'] ?? null);
        $address = $this->text($raw['address'] ?? null);
        $latitude = $this->number($raw['latitude'] ?? null);
        $longitude = $this->number($raw['longitude'] ?? null);
        $errors = [];

        if ($name === '') {
            $errors[] = 'Thiếu tên địa điểm';
        }
        if ($address === '') {
            $errors[] = 'Thiếu địa chỉ';
        }
        if ($latitude === null || $latitude < -90 || $latitude > 90) {
            $errors[] = 'Vĩ độ không hợp lệ';
        }
        if ($longitude === null || $longitude < -180 || $longitude > 180) {
            $errors[] = 'Kinh độ không hợp lệ';
        }

        $row = [
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'external_id' => $this->text($raw['cid'] ?? null) ?: $this->text($raw['place_id'] ?? null),
            'source_category' => $this->text($raw['category'] ?? null),
            'phone' => $this->text($raw['phone'] ?? null),
            'website' => $this->text($raw['website'] ?? null),
            'price_min' => $this->parsePrice($raw['price_range'] ?? null)[0],
            'price_max' => $this->parsePrice($raw['price_range'] ?? null)[1],
            'opening_hours' => $this->json($raw['open_hours'] ?? null),
            'complete_address' => $this->json($raw['complete_address'] ?? null),
        ];
        $row['fingerprint'] = hash('sha256', $this->canonical($name).'|'.$this->canonical($address));

        return ['row' => $row, 'errors' => $errors];
    }

    private function text(mixed $value): string
    {
        return trim(is_scalar($value) ? (string) $value : '');
    }

    private function number(mixed $value): ?float
    {
        $value = $this->text($value);

        return $value === '' || ! is_numeric($value) ? null : (float) $value;
    }

    /** @return array{0: ?int, 1: ?int} */
    private function parsePrice(mixed $value): array
    {
        preg_match_all('/\d[\d.,]*/u', $this->text($value), $matches);
        $prices = array_map(static fn (string $price): int => (int) preg_replace('/\D/', '', $price), $matches[0]);

        return [$prices[0] ?? null, $prices[1] ?? $prices[0] ?? null];
    }

    private function json(mixed $value): ?array
    {
        $decoded = json_decode($this->text($value), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function canonical(string $value): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($value)));
    }
}
