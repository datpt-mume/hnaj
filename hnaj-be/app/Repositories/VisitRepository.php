<?php

namespace App\Repositories;

use App\Enums\PlaceStatus;
use App\Models\AnonymousVisitEvent;
use App\Models\Bookmark;
use App\Models\Place;
use App\Models\VisitEvent;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Persistence cho lượt "Đi tới đó".
 *
 * Visit của User đăng nhập lưu trong `visit_events`; visit của khách lưu trong
 * `anonymous_visit_events` với `anonymous_key_hash` (SHA-256 của định danh tạm
 * thời, không lưu IP thô). Cả hai đều deduplicate theo place + key + ngày nghiệp
 * vụ (visit_date) bằng unique index ở tầng database.
 */
final class VisitRepository
{
    public function isPublicPlace(int $placeId): bool
    {
        return Place::query()
            ->where('id', $placeId)
            ->where('status', PlaceStatus::Active)
            ->where('is_verified', true)
            ->whereNull('deleted_at')
            ->exists();
    }

    public function findUserVisit(int $userId, int $placeId, string $visitDate): ?VisitEvent
    {
        return VisitEvent::query()
            ->where('user_id', $userId)
            ->where('place_id', $placeId)
            ->where('visit_date', $visitDate)
            ->first();
    }

    public function createUserVisit(int $userId, int $placeId, string $visitDate, string $visitedAt, ?string $source): VisitEvent
    {
        return VisitEvent::query()->create([
            'user_id' => $userId,
            'place_id' => $placeId,
            'visit_date' => $visitDate,
            'visited_at' => $visitedAt,
            'source' => $source,
        ]);
    }

    public function findAnonymousVisit(string $keyHash, int $placeId, string $visitDate): ?AnonymousVisitEvent
    {
        return AnonymousVisitEvent::query()
            ->where('anonymous_key_hash', $keyHash)
            ->where('place_id', $placeId)
            ->where('visit_date', $visitDate)
            ->first();
    }

    public function createAnonymousVisit(string $keyHash, int $placeId, string $visitDate, string $visitedAt, ?string $source): AnonymousVisitEvent
    {
        return AnonymousVisitEvent::query()->create([
            'anonymous_key_hash' => $keyHash,
            'place_id' => $placeId,
            'visit_date' => $visitDate,
            'visited_at' => $visitedAt,
            'source' => $source,
        ]);
    }

    /**
     * Lịch sử địa điểm đã "Đi tới đó" của User, unique theo place.
     *
     * Chỉ trả place `active` + `is_verified` + chưa soft-deleted; visit của place
     * hidden/soft-deleted bị ẩn bằng query nhưng không bị xóa. Sắp xếp theo lần
     * đi gần nhất trước. Mỗi item được gán `last_visited_at` và `last_source`.
     *
     * @return LengthAwarePaginator<int, Place>
     */
    public function paginateHistoryForUser(int $userId, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $latestVisit = VisitEvent::query()
            ->select('place_id')
            ->selectRaw('MAX(visited_at) as last_visited_at')
            ->where('user_id', $userId)
            ->groupBy('place_id');

        $paginator = Place::query()
            ->joinSub($latestVisit, 'latest_visits', 'latest_visits.place_id', '=', 'places.id')
            ->where('places.status', PlaceStatus::Active)
            ->where('places.is_verified', true)
            ->whereNull('places.deleted_at')
            ->with(['district', 'category', 'tags', 'thumbnail', 'openingHours'])
            ->select('places.*')
            ->addSelect('latest_visits.last_visited_at')
            ->orderByDesc('latest_visits.last_visited_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $bookmarkedIds = Bookmark::query()
            ->where('user_id', $userId)
            ->whereIn('place_id', $paginator->getCollection()->pluck('id')->all())
            ->pluck('place_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->flip()
            ->all();

        $paginator->getCollection()->transform(function (Place $place) use ($bookmarkedIds): Place {
            $place->bookmark_state = isset($bookmarkedIds[$place->id]);

            return $place;
        });

        $this->attachLastSource($paginator, $userId);

        return $paginator;
    }

    /**
     * Gán `last_source` cho từng place trong trang hiện tại bằng một query duy
     * nhất (tránh N+1). Chỉ lấy nguồn của bản ghi visit gần nhất cho mỗi place.
     *
     * @param  LengthAwarePaginator<int, Place>  $paginator
     */
    private function attachLastSource(LengthAwarePaginator $paginator, int $userId): void
    {
        $placeIds = $paginator->getCollection()
            ->pluck('id')
            ->filter()
            ->all();

        if ($placeIds === []) {
            return;
        }

        $sources = VisitEvent::query()
            ->where('user_id', $userId)
            ->whereIn('place_id', $placeIds)
            ->orderByDesc('visited_at')
            ->orderByDesc('id')
            ->get(['place_id', 'source'])
            ->unique('place_id')
            ->mapWithKeys(static fn (VisitEvent $event): array => [$event->place_id => $event->source]);

        $paginator->getCollection()->transform(function (Place $place) use ($sources): Place {
            $place->setAttribute('last_source', $sources->get($place->id) ?? null);

            return $place;
        });
    }
}