<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Place extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'latitude', 'longitude', 'price_min', 'price_max',
        'tags', 'rating', 'address', 'cover_image', 'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'price_min' => 'integer',
            'price_max' => 'integer',
            'tags' => 'array',
            'rating' => 'float',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    protected function priceDisplay(): Attribute
    {
        return Attribute::get(fn (): string => number_format(($this->price_min + $this->price_max) / 2, 0, ',', '.') . ' VND');
    }
}
