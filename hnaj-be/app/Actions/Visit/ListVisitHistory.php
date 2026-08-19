<?php

namespace App\Actions\Visit;

use App\Repositories\VisitRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Use case danh sách lịch sử "Đi tới đó" của User, unique theo place.
 *
 * Chỉ trả place active và chưa soft-deleted; visit của place ẩn/đã xóa mềm
 * không bị xóa và sẽ hiện lại khi place được khôi phục.
 */
class ListVisitHistory
{
    public function __construct(
        private readonly VisitRepository $visits,
    ) {}

    public function handle(int $userId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        return $this->visits->paginateHistoryForUser($userId, $perPage, $page);
    }
}