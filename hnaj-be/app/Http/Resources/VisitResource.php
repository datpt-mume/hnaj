<?php

namespace App\Http\Resources;

use App\Actions\Visit\RecordedVisit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload kết quả ghi nhận lượt "Đi tới đó" (docs/api-visits.md).
 *
 * Với guest (`anonymous = true`), `id` không được trả để tránh lộ primary key
 * nội bộ của `anonymous_visit_events`.
 *
 * @mixin RecordedVisit
 */
class VisitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'place_id' => $this->placeId,
            'visit_date' => $this->visitDate,
            'visited_at' => $this->visitedAt,
            'source' => $this->source,
            'created' => $this->created,
            'anonymous' => $this->anonymous,
        ];

        if (! $this->anonymous && $this->id !== null) {
            $payload['id'] = $this->id;
        }

        return $payload;
    }
}