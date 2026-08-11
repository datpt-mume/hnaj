<?php

namespace App\Repositories;

use App\Models\Place;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AdminPlaceRepository
{
    /**
     * @return LengthAwarePaginator<int, Place>
     */
    public function verificationQueue(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        $query = Place::query()
            ->where('is_verified', false)
            ->with(['district', 'category', 'tags', 'thumbnail', 'images', 'openingHours'])
            ->orderBy('id', 'asc');

        if (! empty($filters['q'])) {
            $like = '%'.addcslashes($filters['q'], '\\%_').'%';
            $query->where(function ($q) use ($like): void {
                $q->where('name', 'like', $like)
                    ->orWhere('address_text', 'like', $like);
            });
        }

        if (! empty($filters['district_id'])) {
            $query->where('district_id', $filters['district_id']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('tags.id', $filters['tag_id']));
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function findForAdmin(int $id): ?Place
    {
        return Place::query()
            ->with(['district', 'category', 'tags', 'thumbnail', 'images', 'openingHours'])
            ->find($id);
    }

    public function nextUnverifiedId(?int $afterId = null): ?int
    {
        $query = Place::query()
            ->where('is_verified', false)
            ->orderBy('id', 'asc');

        if ($afterId !== null) {
            $query->where('id', '>', $afterId);
        }

        return $query->value('id') !== null ? (int) $query->value('id') : null;
    }

    public function countUnverified(): int
    {
        return Place::query()->where('is_verified', false)->count();
    }

    /**
     * @return Collection<int, Place>
     */
    public function allUnverifiedIds(): Collection
    {
        return Place::query()->where('is_verified', false)->orderBy('id')->get(['id']);
    }
}
