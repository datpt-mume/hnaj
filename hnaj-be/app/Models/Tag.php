<?php

namespace App\Models;

use App\Enums\TagStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'slug', 'status'];

    protected $casts = [
        'status' => TagStatus::class,
    ];

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class, 'place_tags')
            ->withTimestamps();
    }

    public function placeTags(): HasMany
    {
        return $this->hasMany(PlaceTag::class);
    }
}
