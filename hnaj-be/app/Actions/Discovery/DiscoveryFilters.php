<?php

namespace App\Actions\Discovery;

/**
 * Value object mô tả bộ lọc khám phá/random địa điểm.
 *
 * Tất cả thuộc tính đều tùy chọn. `open_now` mặc định là true vì client
 * khám phá luôn muốn lọc place đang mở (docs/prd.md §5.1); gửi `false` để
 * tắt lọc giờ.
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
    ) {}
}
