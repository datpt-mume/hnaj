<?php

namespace App\Actions\Discovery;

/**
 * Value object describing the discovery filters.
 *
 * All properties are optional. `open_now` defaults to true because discovery
 * clients always want open places (docs/prd.md §5.1); send `false` to disable
 * the hour filter.
 *
 * `excludedPlaceIds` is no longer a hard filter: it is the highest-priority
 * ranking criterion (see PlaceScorer), so a just-shown place is only demoted
 * and is still selectable as the sole candidate.
 *
 * `userId` is the personalization context: null for guests, in which case the
 * bookmark and "already visited" criteria do not take part in ranking.
 */
final readonly class DiscoveryFilters
{
    public function __construct(
        public ?int $categoryId = null,
        public ?int $districtId = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        /** @var array<int, int> */
        public array $tagIds = [],
        public bool $openNow = true,
        public ?float $latitude = null,
        public ?float $longitude = null,
        public ?float $radiusKm = null,
        /** @var array<int, int> */
        public array $excludedPlaceIds = [],
        public ?int $userId = null,
    ) {}
}
