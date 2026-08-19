<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload item lịch sử "Đi tới đó" (docs/api-visits.md).
 *
 * Kế thừa shape của PlaceResource và bổ sung `last_visited_at`/`last_source`
 * (lần đi gần nhất tới place).
 *
 * @mixin \App\Models\Place
 */
class VisitHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $place = new PlaceResource($this);

        return array_merge($place->toArray($request), [
            'last_visited_at' => $this->last_visited_at,
            'last_source' => $this->last_source ?? null,
        ]);
    }
}