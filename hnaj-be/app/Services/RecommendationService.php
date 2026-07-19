<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Place;
use Illuminate\Support\Collection;

final class RecommendationService
{
    /** @return array{places: Collection<int, array<string, mixed>>, meta: array<string, mixed>} */
    public function recommend(array $filters): array
    {
        $latitude = (float) $filters['location']['lat'];
        $longitude = (float) $filters['location']['lng'];
        $radius = (float) $filters['radius_km'];
        $priceMin = (int) ($filters['price_min'] ?? 0);
        $priceMax = isset($filters['price_max']) ? (int) $filters['price_max'] : null;
        $tags = $filters['tags'] ?? [];
        $limit = (int) ($filters['limit'] ?? 3);
        $candidateRadii = array_values(array_unique([$radius, min(20, $radius * 1.5), min(20, $radius * 2)]));

        foreach ($candidateRadii as $fallbackLevel => $candidateRadius) {
            $query = Place::query()
                ->with(['category', 'district', 'tags'])
                ->published()
                ->when(isset($filters['district_id']), fn ($query) => $query->where('district_id', $filters['district_id']))
                ->when(isset($filters['category_slug']), fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $filters['category_slug'])))
                ->whereRaw('(price_min + price_max) / 2 >= ?', [$priceMin])
                ->whereBetween('latitude', [$latitude - $candidateRadius / 111, $latitude + $candidateRadius / 111])
                ->whereBetween('longitude', [$longitude - $candidateRadius / 111, $longitude + $candidateRadius / 111]);

            if ($priceMax !== null) {
                $query->whereRaw('(price_min + price_max) / 2 <= ?', [$priceMax]);
            }

            $places = $query
                ->get()
                ->map(function (Place $place) use ($latitude, $longitude, $tags): array {
                    $distance = $this->distanceKm($latitude, $longitude, $place->latitude, $place->longitude);
                    $placeTags = $place->tags->map(fn ($tag): array => [
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'group' => $tag->group_name,
                        'icon' => $tag->icon,
                        'sort_order' => $tag->sort_order,
                    ])->values()->all();
                    $matchedTags = array_values(array_intersect($tags, $place->tags->pluck('slug')->all()));

                    return [
                        'id' => $place->id,
                        'name' => $place->name,
                        'slug' => $place->slug,
                        'category' => $place->category ? ['name' => $place->category->name, 'slug' => $place->category->slug] : null,
                        'district' => $place->district ? ['id' => $place->district->id, 'name' => $place->district->name, 'slug' => $place->district->slug] : null,
                        'cover_image' => $place->cover_image,
                        'distance_km' => $distance,
                        'price_min' => $place->price_min,
                        'price_max' => $place->price_max,
                        'price_display' => $place->price_display,
                        'tags' => $placeTags,
                        'matched_tags' => $matchedTags,
                        'address' => $place->address,
                        'rating' => $place->rating,
                    ];
                })
                ->filter(fn (array $place): bool => $place['distance_km'] <= $candidateRadius)
                ->sortByDesc(fn (array $place): array => [count($place['matched_tags']), $place['rating'], -$place['distance_km']])
                ->values();

            if ($places->count() >= $limit || $fallbackLevel === array_key_last($candidateRadii)) {
                $places = $places->take($limit)->values();

                return [
                    'places' => $places,
                    'meta' => [
                        'total_matched' => $places->count(),
                        'fallback_applied' => $fallbackLevel > 0,
                        'fallback_level' => $fallbackLevel,
                        'query_radius_km' => $candidateRadius,
                        'message_key' => $places->isEmpty() ? 'recommendation.no_results' : 'recommendation.results_found',
                        'relaxed_tags' => false,
                    ],
                ];
            }
        }

        return [
            'places' => collect(),
            'meta' => [
                'total_matched' => 0,
                'fallback_applied' => false,
                'fallback_level' => 0,
                'query_radius_km' => $radius,
                'message_key' => 'recommendation.no_results',
                'relaxed_tags' => false,
            ],
        ];
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
