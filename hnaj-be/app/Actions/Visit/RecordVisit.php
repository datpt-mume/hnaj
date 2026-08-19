<?php

namespace App\Actions\Visit;

use App\Enums\VisitErrorCode;
use App\Exceptions\VisitException;
use App\Models\User;
use App\Repositories\VisitRepository;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Use case ghi nhận một lượt "Đi tới đó".
 *
 * User đăng nhập (user/sub_admin) ghi vào `visit_events`; khách ghi vào
 * `anonymous_visit_events` với hash của định danh tạm thời. `visit_date` tính
 * theo múi giờ Asia/Ho_Chi_Minh để "cùng một ngày" khớp với lịch Việt Nam.
 *
 * Bấm lại cùng place trong cùng ngày là idempotent: trả bản ghi hiện có với
 * `created = false`, không tạo bản ghi mới.
 */
class RecordVisit
{
    public function __construct(
        private readonly VisitRepository $visits,
    ) {}

    public function handle(
        int $placeId,
        ?User $user,
        ?string $anonymousId,
        ?string $source,
    ): RecordedVisit {
        if (! $this->visits->isPublicPlace($placeId)) {
            throw new VisitException(
                VisitErrorCode::PlaceNotAvailable,
                'Địa điểm không tồn tại hoặc không khả dụng.',
                404,
            );
        }

        $now = Carbon::now('Asia/Ho_Chi_Minh');
        $visitDate = $now->toDateString();
        $visitedAt = $now->utc()->toIso8601String();

        if ($user !== null) {
            return $this->recordUserVisit((int) $user->id, $placeId, $visitDate, $visitedAt, $source);
        }

        if ($anonymousId === null || trim($anonymousId) === '') {
            throw new VisitException(
                VisitErrorCode::AnonymousKeyRequired,
                'Thiếu định danh khách truy cập.',
                422,
            );
        }

        return $this->recordAnonymousVisit($anonymousId, $placeId, $visitDate, $visitedAt, $source);
    }

    private function recordUserVisit(
        int $userId,
        int $placeId,
        string $visitDate,
        string $visitedAt,
        ?string $source,
    ): RecordedVisit {
        $existing = $this->visits->findUserVisit($userId, $placeId, $visitDate);

        if ($existing !== null) {
            return new RecordedVisit(
                placeId: $placeId,
                visitDate: $visitDate,
                visitedAt: $existing->visited_at?->toIso8601String(),
                source: $existing->source,
                created: false,
                anonymous: false,
                id: (int) $existing->id,
            );
        }

        try {
            $event = $this->visits->createUserVisit($userId, $placeId, $visitDate, $visitedAt, $source);
        } catch (Throwable $exception) {
            if ($this->isUniqueViolation($exception)) {
                $existing = $this->visits->findUserVisit($userId, $placeId, $visitDate);

                if ($existing !== null) {
                    return new RecordedVisit(
                        placeId: $placeId,
                        visitDate: $visitDate,
                        visitedAt: $existing->visited_at?->toIso8601String(),
                        source: $existing->source,
                        created: false,
                        anonymous: false,
                        id: (int) $existing->id,
                    );
                }
            }

            throw $exception;
        }

        return new RecordedVisit(
            placeId: $placeId,
            visitDate: $visitDate,
            visitedAt: $event->visited_at?->toIso8601String(),
            source: $event->source,
            created: true,
            anonymous: false,
            id: (int) $event->id,
        );
    }

    private function recordAnonymousVisit(
        string $anonymousId,
        int $placeId,
        string $visitDate,
        string $visitedAt,
        ?string $source,
    ): RecordedVisit {
        $keyHash = hash('sha256', $anonymousId);

        $existing = $this->visits->findAnonymousVisit($keyHash, $placeId, $visitDate);

        if ($existing !== null) {
            return new RecordedVisit(
                placeId: $placeId,
                visitDate: $visitDate,
                visitedAt: $existing->visited_at?->toIso8601String(),
                source: $existing->source,
                created: false,
                anonymous: true,
                id: null,
            );
        }

        try {
            $event = $this->visits->createAnonymousVisit($keyHash, $placeId, $visitDate, $visitedAt, $source);
        } catch (Throwable $exception) {
            if ($this->isUniqueViolation($exception)) {
                $existing = $this->visits->findAnonymousVisit($keyHash, $placeId, $visitDate);

                if ($existing !== null) {
                    return new RecordedVisit(
                        placeId: $placeId,
                        visitDate: $visitDate,
                        visitedAt: $existing->visited_at?->toIso8601String(),
                        source: $existing->source,
                        created: false,
                        anonymous: true,
                        id: null,
                    );
                }
            }

            throw $exception;
        }

        return new RecordedVisit(
            placeId: $placeId,
            visitDate: $visitDate,
            visitedAt: $event->visited_at?->toIso8601String(),
            source: $event->source,
            created: true,
            anonymous: true,
            id: null,
        );
    }

    private function isUniqueViolation(Throwable $exception): bool
    {
        $previous = $exception->getPrevious();

        if ($previous === null) {
            return false;
        }

        return (int) $previous->getCode() === 23000
            || ($previous instanceof \PDOException && str_contains($previous->getMessage(), 'Duplicate entry'));
    }
}