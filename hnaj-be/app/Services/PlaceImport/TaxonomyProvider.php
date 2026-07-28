<?php

namespace App\Services\PlaceImport;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Tag;

class TaxonomyProvider
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $categories = Category::query()
            ->where('status', CategoryStatus::Active)
            ->with(['tags' => fn ($query) => $query->where('tags.status', TagStatus::Active)])
            ->get(['id', 'slug', 'name']);

        return [
            'categories' => $categories->map(
                fn (Category $category): array => [
                    'id' => $category->id,
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'allowed_tag_ids' => $category->tags->pluck('id')->values()->all(),
                ],
            )->values()->all(),
            'districts' => District::query()
                ->where('status', DistrictStatus::Active)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(static fn (District $district): array => [
                    'id' => $district->id,
                    'name' => $district->name,
                ])
                ->values()
                ->all(),
            'tags' => Tag::query()
                ->where('status', TagStatus::Active)
                ->orderBy('slug')
                ->get(['id', 'slug', 'name'])
                ->map(static fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
