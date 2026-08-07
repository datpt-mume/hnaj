<?php

namespace App\Actions\Discovery;

/**
 * Personalization context for one discovery round.
 *
 * For guests, `bookmarkedIds` and `visitedIds` are always empty, so the two
 * personalization score components are zero and ranking only depends on
 * excluded ids, proximity and rating.
 */
final readonly class DiscoveryContext
{
    /**
     * @param  array<int, true>  $excludedIds  map placeId => true
     * @param  array<int, true>  $bookmarkedIds  map placeId => true
     * @param  array<int, true>  $visitedIds  map placeId => true
     */
    public function __construct(
        public array $excludedIds = [],
        public array $bookmarkedIds = [],
        public array $visitedIds = [],
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $radiusKm = null,
    ) {}

    /**
     * @param  array<int, int>  $ids
     * @return array<int, true>
     */
    public static function toLookup(array $ids): array
    {
        return array_fill_keys(array_map(static fn (mixed $id): int => (int) $id, $ids), true);
    }
}
