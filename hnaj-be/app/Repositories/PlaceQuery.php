<?php

namespace App\Repositories;

use App\Actions\Discovery\DiscoveryFilters;
use App\Enums\PlaceStatus;
use App\Enums\ScheduleType;
use App\Models\Place;
use App\Models\PlaceOpeningHour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the SQL candidate query and applies PHP-side filters (distance and
 * opening hours) shared by discovery ranking.
 */
class PlaceQuery
{
    public function __construct(
        private readonly int $batchSize = PlaceRepository::MAX_CANDIDATE_IDS,
    ) {}

    /**
     * @return Collection<int, int>
     */
    public function candidateIds(DiscoveryFilters $filters): Collection
    {
        $query = Place::query()
            ->where('status', PlaceStatus::Active)
            ->where('is_verified', true)
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

        $ids = $query
            ->pluck('places.id')
            ->map(static fn (mixed $id): int => (int) $id);

        return $this->filterDistanceAndOpeningHours($ids, $filters);
    }

    /**
     * Filter loaded ids by distance (Haversine) and opening hours in PHP.
     *
     * Process in batches to bound memory, but do NOT stop early: ranking needs
     * every valid candidate to find the best place.
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

        foreach ($ids->chunk($this->batchSize) as $chunk) {
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

    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
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
}
