<?php

namespace App\Actions\Bookmark;

use App\Repositories\BookmarkRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Use case danh sách bookmark riêng tư của User.
 *
 * Chỉ trả về place active và chưa soft-deleted; bản ghi bookmark của place
 * ẩn/đã xóa mềm không bị xóa và sẽ hiện lại khi place được khôi phục.
 */
class ListBookmarks
{
    public function __construct(
        private readonly BookmarkRepository $bookmarks,
    ) {}

    public function handle(int $userId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->bookmarks->paginateForUser($userId, $perPage, $page);
    }
}
