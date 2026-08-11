<?php

namespace App\Actions\Admin\Tag;

use App\Models\Tag;
use App\Repositories\TagRepository;
use Illuminate\Support\Str;

class CreateTag
{
    public function __construct(private readonly TagRepository $tags) {}

    public function handle(string $name): Tag
    {
        $normalizedName = trim(preg_replace('/\s+/', ' ', $name) ?? $name);

        return $this->tags->create($normalizedName, $this->makeUniqueSlug($normalizedName));
    }

    private function makeUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'tag';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->tags->slugExists($slug)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
