<?php

namespace App\Services\PlaceImport;

use App\Models\Place;

class PlaceDuplicateDetector
{
    /** @var array<string, true> */
    private array $seenGooglePlaceIds = [];

    public function reset(): void
    {
        $this->seenGooglePlaceIds = [];
    }

    public function isDuplicate(string $googlePlaceId): bool
    {
        if (isset($this->seenGooglePlaceIds[$googlePlaceId])) {
            return true;
        }

        if (Place::withTrashed()->where('google_place_id', $googlePlaceId)->exists()) {
            return true;
        }

        $this->seenGooglePlaceIds[$googlePlaceId] = true;

        return false;
    }
}
