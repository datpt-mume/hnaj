<?php

namespace App\Actions\Place;

use App\Models\Place;
use App\Repositories\PlaceRepository;

/**
 * Use case chi tiết place công khai cho trang `/places/{place}`.
 *
 * Public endpoint: chỉ place `active`, `is_verified = true`, chưa soft-delete.
 * Khi request có user đã đăng nhập, gán `bookmark_state` để response trả
 * `is_bookmarked` nhất quán với card discovery/search.
 */
class ShowPlace
{
    public function __construct(
        private readonly PlaceRepository $places,
    ) {}

    public function handle(int $placeId, ?int $userId = null): ?Place
    {
        return $this->places->findPublicDetail($placeId, $userId);
    }
}