<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Place extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'latitude', 'longitude', 'price_min', 'price_max',
        'category_id', 'district_id', 'ward_id', 'description', 'website', 'phone', 'opening_hours',
        'amenities', 'gallery', 'source_category', 'rating', 'address', 'cover_image', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'price_min' => 'integer',
            'price_max' => 'integer',
            'category_id' => 'string',
            'district_id' => 'string',
            'ward_id' => 'string',
            'opening_hours' => 'array',
            'amenities' => 'array',
            'gallery' => 'array',
            'rating' => 'float',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'district_id');
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(AdministrativeArea::class, 'ward_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(PlaceExternalId::class);
    }

    protected function priceDisplay(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->price_min === null || $this->price_max === null
            ? null
            : number_format(($this->price_min + $this->price_max) / 2, 0, ',', '.').' VND');
    }
}
