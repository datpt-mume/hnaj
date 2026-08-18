<?php

namespace App\Repositories;

use App\Actions\Discovery\DiscoveryContext;
use App\Actions\Discovery\DiscoveryFilters;
use App\Actions\Discovery\PlaceScorer;
use App\Enums\PlaceStatus;
use App\Enums\TagStatus;
use App\Models\Place;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class PlaceRepository
{
    /**
     * Max candidate ids hydrated at once while filtering/scoring in PHP.
     *
     * This is a processing batch size, NOT a total candidate limit: all ids
     * matching SQL are loaded (ids are integers, so cheap) then processed in
     * batches. Every place gets scored, and the endpoint never reports "not
     * found" while matching places still exist.
     */
    public const MAX_CANDIDATE_IDS = 500;

    /**
     * Max number of place ids the client may send to lower priority in the
     * current round (docs/prd.md §5.1).
     */
    public const MAX_EXCLUDED_IDS = 100;

    public function __construct(
        private readonly PlaceScorer $scorer,
        private readonly PlaceQuery $query,
        private readonly PlacePersonalizationRepository $personalization,
    ) {}

    /**
     * Return the id of the best place matching the filters, or null if none.
     *
     * Indexed filters (category, district, price range, tags ALL) run in SQL;
     * distance and opening hours (open_now) are computed in PHP over the loaded
     * candidate set for MySQL/SQLite compatibility and easy testing. After
     * filtering, every candidate is scored by PlaceScorer and the highest score
     * wins (random tie-break among tied places).
     */
    public function bestPlaceId(?DiscoveryFilters $filters = null): ?int
    {
        $filters ??= new DiscoveryFilters;

        $candidateIds = $this->query->candidateIds($filters);

        if ($candidateIds->isEmpty()) {
            return null;
        }

        return $this->pickBestId($candidateIds, $filters);
    }

    /**
     * Score every candidate and pick the highest-scoring place.
     *
     * Tied candidates are chosen randomly so a discovery round does not always
     * return the same place when the priority criteria cannot tell them apart.
     *
     * @param  Collection<int, int>  $ids
     */
    private function pickBestId(Collection $ids, DiscoveryFilters $filters): int
    {
        $context = $this->buildContext($ids, $filters);

        $bestScore = null;
        /** @var array<int, int> $bestIds */
        $bestIds = [];

        foreach ($ids->chunk(self::MAX_CANDIDATE_IDS) as $chunk) {
            $places = Place::query()
                ->whereKey($chunk->all())
                ->select('places.id', 'places.latitude', 'places.longitude', 'places.rating')
                ->get();

            foreach ($places as $place) {
                $score = $this->scorer->score($place, $context, $this->distanceTo($place, $context));

                if ($bestScore === null || $score > $bestScore) {
                    $bestScore = $score;
                    $bestIds = [(int) $place->id];

                    continue;
                }

                if ($score === $bestScore) {
                    $bestIds[] = (int) $place->id;
                }
            }
        }

        // $ids is non-empty, so at least one candidate was scored.
        return (int) Arr::random($bestIds);
    }

    /**
     * @param  Collection<int, int>  $ids
     */
    private function buildContext(Collection $ids, DiscoveryFilters $filters): DiscoveryContext
    {
        $candidateIds = $ids->all();

        $bookmarked = [];
        $visited = [];

        if ($filters->userId !== null) {
            $bookmarked = $this->personalization->bookmarkedPlaceIds($filters->userId, $candidateIds);
            $visited = $this->personalization->visitedPlaceIds($filters->userId, $candidateIds);
        }

        return new DiscoveryContext(
            excludedIds: DiscoveryContext::toLookup(
                array_slice($filters->excludedPlaceIds, 0, self::MAX_EXCLUDED_IDS),
            ),
            bookmarkedIds: DiscoveryContext::toLookup($bookmarked),
            visitedIds: DiscoveryContext::toLookup($visited),
            latitude: $filters->latitude,
            longitude: $filters->longitude,
            radiusKm: $filters->latitude !== null ? ($filters->radiusKm ?? 5.0) : null,
        );
    }

    private function distanceTo(Place $place, DiscoveryContext $context): ?float
    {
        if ($context->latitude === null || $context->longitude === null) {
            return null;
        }

        return $this->query->haversineKm(
            (float) $place->latitude,
            (float) $place->longitude,
            $context->latitude,
            $context->longitude,
        );
    }

    /**
     * Load một place công khai kèm relations cần thiết cho trang chi tiết.
     *
     * Chỉ trả place `status = active`, `is_verified = true` và chưa soft-delete.
     * `images` chỉ lấy ảnh đang visible; gallery không lộ ảnh đã bị admin ẩn.
     * `bookmark_state` được gán theo user hiện tại (null khi guest) để
     * `PlaceDetailResource` trả `is_bookmarked` nhất quán với card.
     *
     * @param  int  $placeId
     * @param  int|null  $userId  null = guest
     * @return \App\Models\Place|null
     */
    public function findPublicDetail(int $placeId, ?int $userId = null): ?Place
    {
        $place = Place::query()
            ->where('places.id', $placeId)
            ->where('places.status', PlaceStatus::Active)
            ->where('places.is_verified', true)
            ->with([
                'district',
                'category',
                'tags' => fn ($q) => $q->where('tags.status', TagStatus::Active),
                'thumbnail',
                'images' => fn ($q) => $q->where('place_images.is_visible', true)->latest(),
                'openingHours' => fn ($q) => $q->orderBy('day_of_week'),
            ])
            ->first();

        if ($place === null) {
            return null;
        }

        if ($userId !== null) {
            $place->bookmark_state = $this->personalization->isBookmarked($userId, $placeId);
        }

        return $place;
    }
}
