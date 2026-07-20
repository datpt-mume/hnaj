<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Place;
use App\Models\Tag;

final class PlaceData
{
    public static function detail(Place $place, ?float $distanceKm = null): array
    {
        $data = [
            'id' => $place->id,
            'name' => $place->name,
            'slug' => $place->slug,
            'description' => $place->description,
            'category' => self::taxonomy($place->category),
            'district' => self::taxonomy($place->district),
            'ward' => self::taxonomy($place->ward),
            'cover_image' => $place->cover_image,
            'gallery' => $place->gallery ?? [],
            'price_min' => $place->price_min,
            'price_max' => $place->price_max,
            'price_display' => $place->price_display,
            'tags' => collect($place->tags)->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'group' => $tag->group_name,
                'icon' => $tag->icon,
                'sort_order' => $tag->sort_order,
            ])->values()->all(),
            'address' => $place->address,
            'rating' => $place->rating,
        ];

        if ($distanceKm !== null) {
            $data['distance_km'] = $distanceKm;
        }

        return $data;
    }

    private static function taxonomy(?object $taxonomy): ?array
    {
        return $taxonomy ? [
            'id' => $taxonomy->id,
            'name' => $taxonomy->name,
            'slug' => $taxonomy->slug,
        ] : null;
    }
}
