<?php

namespace App\Actions\Discovery;

use App\Models\Place;
use App\Repositories\PlaceRepository;

/**
 * Chọn ngẫu nhiên một place active khớp bộ lọc khám phá.
 *
 * Theo docs/prd.md §5.1: nếu danh sách excluded_place_ids loại hết ứng viên
 * thì bỏ qua excluded và random lại từ đầu, để lượt khám phá không bao giờ
 * rơi vào trạng thái "hết địa điểm chỉ vì roll".
 */
class GetRandomPlace
{
    public function __construct(
        private readonly PlaceRepository $places,
    ) {}

    public function handle(DiscoveryFilters $filters): ?Place
    {
        $placeId = $this->places->randomPlaceId($filters);

        if ($placeId === null && $filters->excludedPlaceIds !== []) {
            // Fallback: random lại không áp dụng excluded.
            $placeId = $this->places->randomPlaceId(
                new DiscoveryFilters(
                    categoryId: $filters->categoryId,
                    districtId: $filters->districtId,
                    minPrice: $filters->minPrice,
                    maxPrice: $filters->maxPrice,
                    tagIds: $filters->tagIds,
                    openNow: $filters->openNow,
                    latitude: $filters->latitude,
                    longitude: $filters->longitude,
                    radiusKm: $filters->radiusKm,
                    excludedPlaceIds: [],
                ),
            );
        }

        if ($placeId === null) {
            return null;
        }

        return Place::query()
            ->with(['district', 'category', 'tags', 'thumbnail', 'openingHours'])
            ->find($placeId);
    }
}
