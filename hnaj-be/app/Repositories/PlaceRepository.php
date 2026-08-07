<?php

namespace App\Repositories;

use App\Actions\Discovery\DiscoveryContext;
use App\Actions\Discovery\DiscoveryFilters;
use App\Actions\Discovery\PlaceScorer;
use App\Enums\PlaceStatus;
use App\Enums\ScheduleType;
use App\Models\Bookmark;
use App\Models\Place;
use App\Models\PlaceOpeningHour;
use App\Models\VisitEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
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

        $candidateIds = $this->queryCandidateIds($filters);

        if ($candidateIds->isEmpty()) {
            return null;
        }

        return $this->pickBestId($candidateIds, $filters);
    }

    /**
     * @param  array<int, int>  $candidateIds
     * @return array<int, int>
     */
    public function bookmarkedPlaceIds(int $userId, array $candidateIds): array
    {
        if ($candidateIds === []) {
            return [];
        }

        return Bookmark::query()
            ->where('user_id', $userId)
            ->whereIn('place_id', $candidateIds)
            ->pluck('place_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $candidateIds
     * @return array<int, int>
     */
    public function visitedPlaceIds(int $userId, array $candidateIds): array
    {
        if ($candidateIds === []) {
            return [];
        }

        return VisitEvent::query()
            ->where('user_id', $userId)
            ->whereIn('place_id', $candidateIds)
            ->distinct()
            ->pluck('place_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    private function queryCandidateIds(DiscoveryFilters $filters): Collection
    {
        $query = Place::query()
            ->where('status', PlaceStatus::Active)
            ->select('places.id');

        if ($filters->categoryId !== null) {
            $query->where('places.category_id', $filters->categoryId);
        }

        if ($filters->districtId !== null) {
            $query->where('places.district_id', $filters->districtId);
        }

        if ($filters->minPrice !== null) {
            $query->where(function ($q) use ($filters): void {
                $q->whereNull('places.max_price')
                    ->orWhere('places.max_price', '>=', $filters->minPrice);
            });
        }

        if ($filters->maxPrice !== null) {
            $query->where(function ($q) use ($filters): void {
                $q->whereNull('places.min_price')
                    ->orWhere('places.min_price', '<=', $filters->maxPrice);
            });
        }

        if ($filters->tagIds !== []) {
            $query->where(function ($q) use ($filters): void {
                foreach (array_unique($filters->tagIds) as $tagId) {
                    $q->whereHas('tags', fn ($tagQ) => $tagQ->where('tags.id', $tagId));
                }
            });
        }

        // excluded_place_ids is no longer a SQL filter: it is a ranking
        // criterion (PlaceScorer::WEIGHT_NOT_EXCLUDED). A just-shown place is
        // only demoted and is still selectable as the sole candidate, replacing
        // the old "re-pick without excluded" fallback.

        // Load all SQL-matching ids (no early cut). An early limit() would make
        // places beyond the first page never selectable, and report "not found"
        // while matching places still exist.
        $ids = $query
            ->pluck('places.id')
            ->map(static fn (mixed $id): int => (int) $id);

        return $this->filterDistanceAndOpeningHours($ids, $filters);
    }

    /**
     * Filter loaded ids by distance (Haversine) and opening hours in PHP.
     *
     * Process in MAX_CANDIDATE_IDS batches to bound memory, but do NOT stop
     * early: ranking needs every valid candidate to find the best place.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, int>
     */
    private function filterDistanceAndOpeningHours(Collection $ids, DiscoveryFilters $filters): Collection
    {
        if ($ids->isEmpty() || (! $filters->openNow && $filters->latitude === null)) {
            return $ids->values();
        }

        $radius = $filters->latitude !== null
            ? ($filters->radiusKm ?? 5.0)
            : null;

        $matched = [];

        foreach ($ids->chunk(self::MAX_CANDIDATE_IDS) as $chunk) {
            $places = Place::query()
                ->whereKey($chunk->all())
                ->with([
                    'openingHours' => fn ($q) => $q->select('place_id', 'day_of_week', 'schedule_type', 'opens_at', 'closes_at'),
                ])
                ->get();

            foreach ($places as $place) {
                if ($this->matchesOpeningNow($place, $filters)
                    && $this->withinRadius($place, $filters, $radius)) {
                    $matched[] = (int) $place->id;
                }
            }
        }

        return new Collection($matched);
    }

    private function matchesOpeningNow(Place $place, DiscoveryFilters $filters): bool
    {
        if (! $filters->openNow) {
            return true;
        }

        $hours = $place->openingHours;

        if ($hours->isEmpty()) {
            // Place chưa có dữ liệu giờ (unknown) vẫn được giữ khi lọc open_now
            // (docs/prd.md §5.1).
            return true;
        }

        // Giờ mở cửa theo quy ước giờ Việt Nam (Asia/Ho_Chi_Minh) của pipeline
        // import, nên dùng timezone cục bộ thay vì timezone mặc định UTC của app.
        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $day = $this->isoDayToStored($now);
        $time = $now->format('H:i');

        $today = $hours->where('day_of_week', $day);

        if ($today->isEmpty()) {
            // Không khai báo ngày hôm nay => được hiểu là mở (unknown).
            return true;
        }

        return $today->contains(fn (PlaceOpeningHour $slot): bool => $this->slotOpenAt($slot, $time));
    }

    private function slotOpenAt(PlaceOpeningHour $slot, string $time): bool
    {
        if ($slot->schedule_type === ScheduleType::AllDay) {
            return true;
        }

        if ($slot->schedule_type === ScheduleType::Closed) {
            return false;
        }

        return $slot->opens_at !== null
            && $slot->closes_at !== null
            && $time >= $slot->opens_at
            && $time <= $slot->closes_at;
    }

    private function withinRadius(Place $place, DiscoveryFilters $filters, ?float $radius): bool
    {
        if ($filters->latitude === null || $filters->longitude === null || $radius === null) {
            return true;
        }

        $distance = $this->haversineKm(
            (float) $place->latitude,
            (float) $place->longitude,
            $filters->latitude,
            $filters->longitude,
        );

        return $distance <= $radius;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Convert Carbon isoWeekday (1=Mon ... 7=Sun) to the stored convention
     * (2=Mon ... 8=Sun) used by the import pipeline.
     */
    private function isoDayToStored(Carbon $date): int
    {
        $isoDay = $date->isoWeekday(); // 1=T2 ... 7=CN

        return $isoDay + 1;
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
            $bookmarked = $this->bookmarkedPlaceIds($filters->userId, $candidateIds);
            $visited = $this->visitedPlaceIds($filters->userId, $candidateIds);
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

        return $this->haversineKm(
            (float) $place->latitude,
            (float) $place->longitude,
            $context->latitude,
            $context->longitude,
        );
    }
}
