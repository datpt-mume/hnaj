<?php

namespace App\Services\PlaceImport;

use App\Models\Place;

class PlaceDuplicateDetector
{
    /** @var array<string, true> */
    private array $seenKeys = [];

    /**
     * @param  array<string, mixed>  $record
     */
    public function isDuplicate(array $record): bool
    {
        $googlePlaceId = $record['google_place_id'] ?? null;
        $googleKey = is_string($googlePlaceId) && $googlePlaceId !== ''
            ? 'google:'.$googlePlaceId
            : null;

        if ($googleKey !== null && isset($this->seenKeys[$googleKey])) {
            return true;
        }

        if ($googleKey !== null && Place::query()->where('google_place_id', $googlePlaceId)->exists()) {
            return true;
        }

        if ($googleKey !== null) {
            $this->seenKeys[$googleKey] = true;
        }

        $coordinateKey = $this->coordinateKey($record);

        if ($coordinateKey !== null && isset($this->seenKeys[$coordinateKey])) {
            return true;
        }

        if ($coordinateKey !== null) {
            $exists = Place::query()
                ->whereBetween('latitude', [(float) $record['latitude'] - 0.00001, (float) $record['latitude'] + 0.00001])
                ->whereBetween('longitude', [(float) $record['longitude'] - 0.00001, (float) $record['longitude'] + 0.00001])
                ->exists();

            if ($exists) {
                return true;
            }

            $this->seenKeys[$coordinateKey] = true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function coordinateKey(array $record): ?string
    {
        $latitude = $record['latitude'] ?? null;
        $longitude = $record['longitude'] ?? null;

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        return implode('|', [
            'coordinates',
            number_format((float) $latitude, 5, '.', ''),
            number_format((float) $longitude, 5, '.', ''),
        ]);
    }
}
