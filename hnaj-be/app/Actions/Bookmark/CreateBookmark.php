<?php

namespace App\Actions\Bookmark;

use App\Enums\PlaceStatus;
use App\Exceptions\BookmarkException;
use App\Enums\BookmarkErrorCode;
use App\Models\Bookmark;
use App\Models\Place;
use App\Repositories\BookmarkRepository;

/**
 * Use case lưu bookmark riêng tư cho User.
 *
 * Chỉ cho phép bookmark place active và chưa soft-deleted; bản ghi trùng
 * User/place bị chặn bởi unique index và trả lỗi riêng cho client.
 */
class CreateBookmark
{
    public function __construct(
        private readonly BookmarkRepository $bookmarks,
    ) {}

    public function handle(int $userId, int $placeId): Bookmark
    {
        $place = Place::query()
            ->where('id', $placeId)
            ->where('status', PlaceStatus::Active)
            ->first();

        if ($place === null) {
            throw new BookmarkException(
                BookmarkErrorCode::PlaceNotAvailable,
                'Địa điểm không tồn tại hoặc không khả dụng.',
                404,
            );
        }

        if ($this->bookmarks->exists($userId, $placeId)) {
            throw new BookmarkException(
                BookmarkErrorCode::AlreadyExists,
                'Địa điểm đã được lưu.',
                409,
            );
        }

        return $this->bookmarks->create($userId, $placeId);
    }
}
