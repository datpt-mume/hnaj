<?php

namespace App\Services\PlaceImport;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\PlaceStatus;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

class PlaceImportPersistence
{
    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $classification
     */
    public function import(array $record, array $classification): ?Place
    {
        return DB::transaction(function () use ($record, $classification): ?Place {
            if (Place::withTrashed()->where('google_place_id', $record['google_place_id'])->exists()) {
                return null;
            }

            $category = Category::query()
                ->whereKey($classification['category_id'])
                ->where('status', CategoryStatus::Active)
                ->firstOrFail();
            $district = District::query()
                ->whereKey($classification['district_id'])
                ->where('status', DistrictStatus::Active)
                ->firstOrFail();
            $tagIds = array_values(array_unique($classification['tag_ids']));
            $tags = Tag::query()
                ->whereKey($tagIds)
                ->where('status', TagStatus::Active)
                ->get();

            if ($tags->count() !== count($tagIds)) {
                throw new \InvalidArgumentException('One or more classified tags are unavailable.');
            }

            $place = Place::query()->create([
                'name' => $record['name'],
                'address_text' => $classification['normalized_address'],
                'google_place_id' => $record['google_place_id'],
                'phone' => $record['phone'],
                'website_url' => $record['website_url'],
                'google_maps_url' => $record['google_maps_url'],
                'district_id' => $district->id,
                'category_id' => $category->id,
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
                'min_price' => $classification['min_price_vnd'],
                'max_price' => $classification['max_price_vnd'],
                'description' => $record['description'],
                'status' => PlaceStatus::Active,
                'created_by' => null,
                'thumbnail_image_id' => null,
            ]);

            if ($tags->isNotEmpty()) {
                $place->tags()->attach($tags->pluck('id')->all());
            }

            foreach ($classification['opening_hours'] as $openingHour) {
                $place->openingHours()->create($openingHour);
            }

            $thumbnailUrl = $record['thumbnail_url'];

            if (is_string($thumbnailUrl) && $thumbnailUrl !== '') {
                $image = $place->images()->create([
                    'uploaded_by' => null,
                    'image_url' => $thumbnailUrl,
                    'alt_text' => $record['name'],
                    'is_visible' => true,
                ]);

                $place->update(['thumbnail_image_id' => $image->id]);
            }

            return $place;
        });
    }
}
