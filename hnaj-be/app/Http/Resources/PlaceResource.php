<?php

namespace App\Http\Resources;

use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload card địa điểm cho kết quả random khám phá.
 *
 * Chỉ expose field công khai cần thiết cho card; FE tự dựng link Google Maps
 * directions từ `google_maps_url` hoặc tọa độ.
 *
 * @mixin Place
 */
class PlaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address_text' => $this->address_text,
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district->id,
                'name' => $this->district->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'thumbnail' => $this->whenLoaded('thumbnail', fn () => $this->thumbnail ? [
                'image_url' => $this->thumbnail->image_url,
                'alt_text' => $this->thumbnail->alt_text,
            ] : null),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'google_maps_url' => $this->google_maps_url,
            'opening_hours' => $this->whenLoaded('openingHours', fn () => OpeningHourResource::collection($this->openingHours)),
        ];
    }
}
