<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AdministrativeArea;
use App\Models\Category;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DiscoveryOptionsController extends Controller
{
    private const DISTRICT_CENTERS = [
        'ba-dinh-ha-noi' => [21.0341, 105.8142],
        'cau-giay-ha-noi' => [21.0362, 105.7906],
        'dong-da-ha-noi' => [21.0183, 105.8291],
        'ha-dong-ha-noi' => [20.9712, 105.7788],
        'hai-ba-trung-ha-noi' => [21.0058, 105.8577],
        'hoan-kiem-ha-noi' => [21.0285, 105.8542],
        'hoang-mai-ha-noi' => [20.9745, 105.8633],
        'long-bien-ha-noi' => [21.0549, 105.8923],
        'tay-ho-ha-noi' => [21.0812, 105.8188],
        'thanh-xuan-ha-noi' => [20.9938, 105.8113],
    ];

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'sort_order']);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function districts(): JsonResponse
    {
        $districts = AdministrativeArea::query()
            ->where('type', 'district')
            ->where('city', 'Hà Nội')
            ->where('is_active', true)
            ->withAvg(['placesAsDistrict as center_lat' => fn ($query) => $query->published()], 'latitude')
            ->withAvg(['placesAsDistrict as center_lng' => fn ($query) => $query->published()], 'longitude')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(function (AdministrativeArea $district): array {
                $fallback = self::DISTRICT_CENTERS[$district->slug] ?? null;

                return [
                    'id' => $district->id,
                    'name' => $district->name,
                    'slug' => $district->slug,
                    'center' => $district->center_lat !== null && $district->center_lng !== null ? [
                        'lat' => (float) $district->center_lat,
                        'lng' => (float) $district->center_lng,
                    ] : ($fallback ? ['lat' => $fallback[0], 'lng' => $fallback[1]] : null),
                ];
            });

        return response()->json(['success' => true, 'data' => $districts]);
    }

    public function tags(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['sometimes', 'string', 'exists:categories,slug'],
            'selected' => ['sometimes', 'array', 'max:20'],
            'selected.*' => ['string', 'exists:tags,slug'],
        ]);

        $categorySlug = $validated['category'] ?? null;
        $selected = $validated['selected'] ?? [];
        $tagQuery = Tag::query()
            ->where('is_active', true)
            ->when($categorySlug, fn ($query) => $query->whereHas(
                'categories',
                fn ($categoryQuery) => $categoryQuery->where('slug', $categorySlug),
            ));
        $tags = $tagQuery
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'group_name', 'icon', 'sort_order']);

        if ($tags->isEmpty() && $categorySlug) {
            $tags = Tag::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug', 'group_name', 'icon', 'sort_order']);
        }

        $relatedSlugs = $this->relatedTagSlugs($selected, $categorySlug);
        $data = $tags->map(fn (Tag $tag): array => [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'group' => $tag->group_name,
            'icon' => $tag->icon,
            'sort_order' => $tag->sort_order,
            'is_related' => in_array($tag->slug, $relatedSlugs, true),
        ])->sortBy(fn (array $tag): array => [
            in_array($tag['slug'], $selected, true) ? 0 : ($tag['is_related'] ? 1 : 2),
            $tag['sort_order'],
        ])->values();

        return response()->json([
            'success' => true,
            'data' => ['tags' => $data, 'related' => array_slice($relatedSlugs, 0, 6)],
        ]);
    }

    /** @param array<int, string> $selected */
    private function relatedTagSlugs(array $selected, ?string $categorySlug): array
    {
        if ($selected === []) {
            return [];
        }

        return Place::query()
            ->published()
            ->when($categorySlug, fn ($query) => $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('slug', $categorySlug)))
            ->whereHas('tags', fn ($query) => $query->whereIn('slug', $selected))
            ->with('tags:id,slug')
            ->get()
            ->flatMap(fn (Place $place) => $place->tags->pluck('slug'))
            ->reject(fn (string $slug): bool => in_array($slug, $selected, true))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->values()
            ->all();
    }
}