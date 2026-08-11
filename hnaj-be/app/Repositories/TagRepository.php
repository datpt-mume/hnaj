<?php

namespace App\Repositories;

use App\Enums\TagStatus;
use App\Models\Tag;

class TagRepository
{
    public function create(string $name, string $slug): Tag
    {
        return Tag::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => TagStatus::Active,
        ]);
    }

    public function slugExists(string $slug): bool
    {
        return Tag::withTrashed()->where('slug', $slug)->exists();
    }
}
