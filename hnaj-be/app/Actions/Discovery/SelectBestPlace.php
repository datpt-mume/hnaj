<?php

namespace App\Actions\Discovery;

use App\Models\Place;
use App\Repositories\BookmarkRepository;
use App\Repositories\PlaceRepository;

/**
 * Select the best available place matching the discovery filters.
 *
 * This is no longer pure random. After hard filtering by the user's filters,
 * every remaining candidate is scored (see PlaceScorer) by priority order:
 * not recently shown → bookmarked → "go there" visited → closer → higher
 * rating. Tied candidates are picked randomly.
 *
 * Because excluded_place_ids only lowers the score instead of removing
 * candidates, the old "drop excluded and re-pick" fallback is unnecessary:
 * a discovery round never empties just because of rolling.
 */
class SelectBestPlace
{
    public function __construct(
        private readonly PlaceRepository $places,
        private readonly BookmarkRepository $bookmarks,
    ) {}

    public function handle(DiscoveryFilters $filters): ?Place
    {
        $placeId = $this->places->bestPlaceId($filters);

        if ($placeId === null) {
            return null;
        }

        $place = Place::query()
            ->with(['district', 'category', 'tags', 'thumbnail', 'openingHours'])
            ->find($placeId);

        if ($place !== null && $filters->userId !== null) {
            // Gán cờ để PlaceResource trả is_bookmarked khi request có user
            // (docs/api-bookmarks.md).
            $place->bookmark_state = $this->bookmarks->exists($filters->userId, (int) $place->id);
        }

        return $place;
    }
}
