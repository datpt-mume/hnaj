<?php

namespace App\Http\Resources;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload bookmark địa điểm yêu thích.
 *
 * Trả place đã eager-load kèm trạng thái is_bookmarked = true vì bản ghi này
 * thuộc chính user vừa tạo.
 *
 * @mixin Bookmark
 */
class BookmarkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'place' => $this->whenLoaded('place', fn () => new PlaceResource($this->place)),
            'place_id' => $this->place_id,
            'is_bookmarked' => true,
        ];
    }
}
