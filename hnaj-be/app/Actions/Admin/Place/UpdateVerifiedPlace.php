<?php

namespace App\Actions\Admin\Place;

use App\Enums\ScheduleType;
use App\Models\Place;
use App\Models\PlaceImage;
use Illuminate\Support\Facades\DB;

class UpdateVerifiedPlace
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(Place $place, array $validated): Place
    {
        return DB::transaction(function () use ($place, $validated): Place {
            $place->update([
                'name' => $validated['name'],
                'address_text' => $validated['address_text'],
                'district_id' => $validated['district_id'],
                'category_id' => $validated['category_id'],
                'phone' => $validated['phone'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'google_maps_url' => $validated['google_maps_url'],
                'google_place_id' => $validated['google_place_id'] ?? $place->google_place_id,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'min_price' => $validated['min_price'] ?? null,
                'max_price' => $validated['max_price'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'is_verified' => true,
            ]);

            if (array_key_exists('tag_ids', $validated)) {
                $place->tags()->sync($validated['tag_ids'] ?? []);
            }

            if (array_key_exists('opening_hours', $validated) && is_array($validated['opening_hours'])) {
                $place->openingHours()->delete();
                foreach ($validated['opening_hours'] as $hour) {
                    $place->openingHours()->create([
                        'day_of_week' => $hour['day_of_week'],
                        'schedule_type' => ScheduleType::from($hour['schedule_type']),
                        'opens_at' => $hour['opens_at'] ?? null,
                        'closes_at' => $hour['closes_at'] ?? null,
                    ]);
                }
            }

            $deletedIds = $validated['deleted_image_ids'] ?? [];
            if ($deletedIds !== []) {
                $place->images()->whereIn('id', $deletedIds)->delete();
                if ($place->thumbnail_image_id !== null && in_array($place->thumbnail_image_id, $deletedIds, true)) {
                    $place->update(['thumbnail_image_id' => null]);
                }
            }

            $images = $validated['images'] ?? null;
            if (is_array($images)) {
                foreach ($images as $img) {
                    if (! empty($img['id'])) {
                        $existing = PlaceImage::query()->where('place_id', $place->id)->find($img['id']);
                        if ($existing) {
                            $existing->update([
                                'image_url' => $img['image_url'],
                                'alt_text' => $img['alt_text'] ?? null,
                            ]);
                        }
                    } else {
                        PlaceImage::query()->create([
                            'place_id' => $place->id,
                            'image_url' => $img['image_url'],
                            'alt_text' => $img['alt_text'] ?? null,
                            'is_visible' => true,
                        ]);
                    }
                }
            }

            if (array_key_exists('thumbnail_image_id', $validated)) {
                $thumbId = $validated['thumbnail_image_id'];
                if ($thumbId !== null) {
                    $belongs = PlaceImage::query()->where('place_id', $place->id)->where('id', $thumbId)->exists();
                    $place->update(['thumbnail_image_id' => $belongs ? $thumbId : null]);
                } else {
                    $place->update(['thumbnail_image_id' => null]);
                }
            }

            return $place->fresh(['district', 'category', 'tags', 'thumbnail', 'images', 'openingHours']) ?? $place;
        });
    }
}
