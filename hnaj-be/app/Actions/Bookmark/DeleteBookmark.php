<?php

namespace App\Actions\Bookmark;

use App\Exceptions\BookmarkException;
use App\Enums\BookmarkErrorCode;
use App\Repositories\BookmarkRepository;

/**
 * Use case bỏ bookmark riêng tư của User theo place_id.
 *
 * Xóa theo cặp user_id + place_id; user chỉ có thể xóa bookmark của chính mình.
 */
class DeleteBookmark
{
    public function __construct(
        private readonly BookmarkRepository $bookmarks,
    ) {}

    public function handle(int $userId, int $placeId): void
    {
        if (! $this->bookmarks->delete($userId, $placeId)) {
            throw new BookmarkException(
                BookmarkErrorCode::NotFound,
                'Không tìm thấy bookmark để xóa.',
                404,
            );
        }
    }
}
