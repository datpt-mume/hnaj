<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use Illuminate\Support\Collection;

final class RecommendationService
{
    public function recommend(array $filters): Collection
    {
        $latitude = (float) $filters['location']['lat'];
        $longitude = (float) $filters['location']['lng'];
        $radius = (float) $filters['radius_km'];
        $priceMax = (int) $filters['price_max'];
        $tags = $filters['tags'] ?? [];

        $places = Place::query()
            ->published()
            ->whereRaw('(price_min + price_max) / 2 <= ?', [$priceMax])
            ->whereBetween('latitude', [$latitude - $radius / 111, $latitude + $radius / 111])
            ->whereBetween('longitude', [$longitude - $radius / 111, $longitude + $radius / 111])
            ->get()
            ->map(function (Place $place) use ($latitude, $longitude, $tags): array {
                $distance = $this->distanceKm($latitude, $longitude, $place->latitude, $place->longitude);
                $matchedTags = array_values(array_intersect($tags, $place->tags ?? []));

                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'slug' => $place->slug,
                    'cover_image' => $place->cover_image,
                    'distance_km' => $distance,
                    'price_min' => $place->price_min,
                    'price_max' => $place->price_max,
                    'price_display' => $place->price_display,
                    'tags' => $place->tags ?? [],
                    'matched_tags' => $matchedTags,
                    'address' => $place->address,
                    'rating' => $place->rating,
                ];
            })
            ->filter(fn (array $place): bool => $place['distance_km'] <= $radius)
            ->sortByDesc(fn (array $place): array => [count($place['matched_tags']), $place['rating'], -$place['distance_km']]);

        return $places->take((int) ($filters['limit'] ?? 3))->values();
    }

    private function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        return $earthRadius * 2 * asin(min(1, sqrt($a)));
    }
}
