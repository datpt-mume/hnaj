<?php

namespace App\Http\Resources;

use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Place
 */
class AdminPlaceResource extends JsonResource
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
            'google_place_id' => $this->google_place_id,
            'phone' => $this->phone,
            'website_url' => $this->website_url,
            'google_maps_url' => $this->google_maps_url,
            'district' => $this->whenLoaded('district', fn () => $this->district ? [
                'id' => $this->district->id,
                'name' => $this->district->name,
            ] : null),
            'district_id' => $this->district_id,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'category_id' => $this->category_id,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'rating' => $this->rating !== null ? (float) $this->rating : null,
            'description' => $this->description,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'is_verified' => (bool) $this->is_verified,
            'thumbnail_image_id' => $this->thumbnail_image_id,
            'thumbnail' => $this->whenLoaded('thumbnail', fn () => $this->thumbnail ? [
                'id' => $this->thumbnail->id,
                'image_url' => $this->thumbnail->image_url,
                'alt_text' => $this->thumbnail->alt_text,
            ] : null),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'alt_text' => $img->alt_text,
                'is_visible' => (bool) $img->is_visible,
            ])->values()),
            'opening_hours' => $this->whenLoaded('openingHours', fn () => OpeningHourResource::collection($this->openingHours)),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
