<?php

namespace App\Repositories;

use App\Enums\CategoryStatus;
use App\Enums\DistrictStatus;
use App\Enums\TagStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class DiscoveryMetadataRepository
{
    /**
     * @return Collection<int, Category>
     */
    public function activeCategories(): Collection
    {
        return Category::query()
            ->where('status', CategoryStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    /**
     * @return Collection<int, District>
     */
    public function activeDistricts(): Collection
    {
        return District::query()
            ->where('status', DistrictStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * @return Collection<int, Tag>
     */
    public function activeTags(): Collection
    {
        return Tag::query()
            ->where('status', TagStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
