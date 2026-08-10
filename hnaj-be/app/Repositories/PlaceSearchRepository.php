<?php

namespace App\Repositories;

use App\Enums\PlaceStatus;
use App\Models\Place;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public place search: tokenized LIKE query over name, address, tag and
 * category names, sorted by rating desc then name asc (docs/api-search.md).
 */
class PlaceSearchRepository
{
    /**
     * @return LengthAwarePaginator<int, Place>
     */
    public function search(string $query, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $tokens = preg_split('/\s+/u', trim($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $query = Place::query()
            ->where('status', PlaceStatus::Active)
            ->with(['district', 'category', 'tags', 'thumbnail', 'openingHours']);

        foreach ($tokens as $token) {
            // Escape LIKE wildcards so a literal "%" or "_" in the query does
            // not widen the match (MySQL default escape char is backslash).
            $like = '%'.addcslashes($token, '\\%_').'%';
            $query->where(function ($q) use ($like): void {
                $q->where('places.name', 'like', $like)
                    ->orWhere('places.address_text', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('categories.name', 'like', $like))
                    ->orWhereHas('tags', fn ($t) => $t->where('tags.name', 'like', $like));
            });
        }

        return $query
            ->orderBy('places.rating', 'desc')
            ->orderBy('places.name', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
