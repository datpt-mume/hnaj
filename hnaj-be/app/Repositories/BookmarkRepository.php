<?php

namespace App\Repositories;

use App\Enums\PlaceStatus;
use App\Models\Bookmark;
use App\Models\Place;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Persistence cho bookmark riêng tư của User.
 *
 * Bookmark chỉ hiển thị cho chính User tạo ra; place bị ẩn hoặc soft-deleted
 * được loại khỏi danh sách bằng query nhưng bản ghi bookmark không bị xóa
 * (docs/prd.md §5.3, docs/erd.md).
 */
final class BookmarkRepository
{
    /**
     * @return LengthAwarePaginator<int, Place>
     */
    public function paginateForUser(int $userId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $paginator = Place::query()
            ->join('bookmarks', 'bookmarks.place_id', '=', 'places.id')
            ->where('bookmarks.user_id', $userId)
            ->where('places.status', PlaceStatus::Active)
            ->whereNull('places.deleted_at')
            ->with(['district', 'category', 'tags', 'thumbnail', 'openingHours'])
            ->select('places.*')
            ->orderBy('bookmarks.created_at', 'desc')
            ->orderBy('bookmarks.id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $paginator->through(function (Place $place): Place {
            // Gán cờ để PlaceResource trả is_bookmarked = true mà không phát
            // sinh query thêm (docs/api-bookmarks.md).
            $place->bookmark_state = true;

            return $place;
        });
    }

    public function exists(int $userId, int $placeId): bool
    {
        return Bookmark::query()
            ->where('user_id', $userId)
            ->where('place_id', $placeId)
            ->exists();
    }

    public function create(int $userId, int $placeId): Bookmark
    {
        return Bookmark::query()->create([
            'user_id' => $userId,
            'place_id' => $placeId,
        ]);
    }

    public function delete(int $userId, int $placeId): bool
    {
        return (bool) Bookmark::query()
            ->where('user_id', $userId)
            ->where('place_id', $placeId)
            ->delete();
    }
}
