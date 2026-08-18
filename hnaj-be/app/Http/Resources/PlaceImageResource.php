<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload ảnh place trong gallery chi tiết.
 *
 * Chỉ expose `image_url` và `alt_text`; không lộ `uploaded_by`, `is_visible`
 * hoặc soft-delete state — UI chỉ cần link ảnh và mô tả.
 *
 * @mixin \App\Models\PlaceImage
 */
class PlaceImageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'image_url' => $this->image_url,
            'alt_text' => $this->alt_text,
        ];
    }
}
