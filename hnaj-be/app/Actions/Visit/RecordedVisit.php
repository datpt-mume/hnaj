<?php

namespace App\Actions\Visit;

/**
 * Kết quả ghi nhận một lượt "Đi tới đó", dùng để dựng response mà không lộ
 * chi tiết nội bộ. Với guest (`anonymous = true`), `id` luôn null để tránh rò
 * rỉ primary key của bảng anonymous_visit_events.
 */
final class RecordedVisit
{
    public function __construct(
        public readonly int $placeId,
        public readonly string $visitDate,
        public readonly ?string $visitedAt,
        public readonly ?string $source,
        public readonly bool $created,
        public readonly bool $anonymous,
        public readonly ?int $id,
    ) {}
}