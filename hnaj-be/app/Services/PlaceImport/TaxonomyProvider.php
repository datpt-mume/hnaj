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
            'categories' => $categories->mapWithKeys(
                fn (Category $category): array => [$category->slug => [
                    'name' => $category->name,
                    'allowed_tag_slugs' => $category->tags->pluck('slug')->values()->all(),
                ]],
            )->all(),
            'district_names' => District::query()
                ->where('status', DistrictStatus::Active)
                ->orderBy('name')
                ->pluck('name')
                ->values()
                ->all(),
            'tag_slugs' => Tag::query()
                ->where('status', TagStatus::Active)
                ->orderBy('slug')
                ->pluck('slug')
                ->values()
                ->all(),
        ];
    }
}
