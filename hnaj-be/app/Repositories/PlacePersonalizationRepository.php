<?php

namespace App\Repositories;

use App\Models\Bookmark;
use App\Models\VisitEvent;

final class PlacePersonalizationRepository
{
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
     * Kiểm tra nhanh một place đã được user bookmark chưa.
     *
     * Dùng cho trang chi tiết để gán `bookmark_state` mà không cần load
     * toàn bộ candidate set như `bookmarkedPlaceIds`.
     */
    public function isBookmarked(int $userId, int $placeId): bool
    {
        return Bookmark::query()
            ->where('user_id', $userId)
            ->where('place_id', $placeId)
            ->exists();
    }
}
