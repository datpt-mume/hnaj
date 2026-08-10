<?php

namespace App\Actions\Place;

use App\Repositories\PlaceSearchRepository;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Use case tìm kiếm địa điểm công khai.
 *
 * Trả về danh sách place active khớp query, sort theo rating giảm dần rồi
 * đến name tăng dần, phân trang page-based (docs/api-search.md).
 */
class SearchPlaces
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 50;

    public const MAX_QUERY_LENGTH = 100;

    public function __construct(
        private readonly PlaceSearchRepository $places,
    ) {}

    public function handle(string $query, int $perPage = self::DEFAULT_PER_PAGE, int $page = 1): LengthAwarePaginator
    {
        return $this->places->search($query, $perPage, $page);
    }
}
