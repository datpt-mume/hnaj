<?php

namespace App\Models;

use App\Enums\CategoryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'status'];

    protected $casts = [
        'status' => CategoryStatus::class,
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'category_tags')
            ->withTimestamps();
    }

    public function categoryTags(): HasMany
    {
        return $this->hasMany(CategoryTag::class);
    }
}
