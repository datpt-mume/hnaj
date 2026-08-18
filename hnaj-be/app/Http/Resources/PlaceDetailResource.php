<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Payload chi tiết place cho trang `/places/{place}`.
 *
 * Mở rộng [`PlaceResource`](PlaceResource.php) với các field card không cần
 * thiết: mô tả, liên hệ, gallery ảnh, cờ đã xác minh và lịch mở cửa đầy đủ.
 *
 * @mixin \App\Models\Place
 */
class PlaceDetailResource extends PlaceResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'description' => $this->description,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            // Cờ đã xác minh; place active công khai luôn true, nhưng expose để
            // UI có thể hiển thị badge mà không cần suy luận từ status.
            'is_verified' => (bool) $this->is_verified,
            'images' => PlaceImageResource::collection($this->whenLoaded('images')),
            // Ghi đè opening_hours từ parent để luôn dùng collection đã eager-load.
            'opening_hours' => OpeningHourResource::collection($this->whenLoaded('openingHours')),
        ]);
    }
}
