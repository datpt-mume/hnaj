<?php

namespace App\Actions\Admin\Place;

use App\Enums\ScheduleType;
use App\Enums\PlaceStatus;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAdminPlace
{
    /**
     * @param  array<string, mixed>  $validated
     */
    public function handle(User $admin, array $validated): Place
    {
        return DB::transaction(function () use ($admin, $validated): Place {
            $place = Place::query()->create([
                'name' => $validated['name'],
                'address_text' => $validated['address_text'],
                'district_id' => $validated['district_id'],
                'category_id' => $validated['category_id'],
                'phone' => $validated['phone'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'google_maps_url' => $validated['google_maps_url'],
                'google_place_id' => $validated['google_place_id'] ?? null,
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'min_price' => $validated['min_price'] ?? null,
                'max_price' => $validated['max_price'] ?? null,
                'description' => $validated['description'] ?? null,
                'status' => PlaceStatus::from($validated['status']),
                'is_verified' => true,
                'created_by' => $admin->id,
            ]);

            if (! empty($validated['tag_ids'])) {
                $place->tags()->attach($validated['tag_ids']);
            }

            if (! empty($validated['opening_hours']) && is_array($validated['opening_hours'])) {
                foreach ($validated['opening_hours'] as $hour) {
                    $place->openingHours()->create([
                        'day_of_week' => $hour['day_of_week'],
                        'schedule_type' => ScheduleType::from($hour['schedule_type']),
                        'opens_at' => $hour['opens_at'] ?? null,
                        'closes_at' => $hour['closes_at'] ?? null,
                    ]);
                }
            }

            if (! empty($validated['images']) && is_array($validated['images'])) {
                $firstId = null;
                foreach ($validated['images'] as $img) {
                    $image = PlaceImage::query()->create([
                        'place_id' => $place->id,
                        'image_url' => $img['image_url'],
                        'alt_text' => $img['alt_text'] ?? null,
                        'is_visible' => true,
                        'uploaded_by' => $admin->id,
                    ]);
                    if ($firstId === null) {
                        $firstId = $image->id;
                    }
                }

                if (! empty($validated['thumbnail_image_id']) && PlaceImage::query()
                    ->where('place_id', $place->id)
                    ->where('id', $validated['thumbnail_image_id'])
                    ->exists()) {
                    $place->update(['thumbnail_image_id' => $validated['thumbnail_image_id']]);
                } elseif ($firstId !== null) {
                    $place->update(['thumbnail_image_id' => $firstId]);
                }
            }

            return $place->fresh(['district', 'category', 'tags', 'thumbnail', 'images', 'openingHours']) ?? $place;
        });
    }
}
