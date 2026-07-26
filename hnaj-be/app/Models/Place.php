<?php

namespace App\Models;

use App\Enums\PlaceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Place extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address_text',
        'google_place_id',
        'phone',
        'website_url',
        'google_maps_url',
        'district_id',
        'category_id',
        'latitude',
        'longitude',
        'min_price',
        'max_price',
        'description',
        'thumbnail_image_id',
        'status',
        'created_by',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'min_price' => 'integer',
        'max_price' => 'integer',
        'status' => PlaceStatus::class,
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function thumbnail(): BelongsTo
    {
        return $this->belongsTo(PlaceImage::class, 'thumbnail_image_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'place_tags')
            ->withTimestamps();
    }

    public function openingHours(): HasMany
    {
        return $this->hasMany(PlaceOpeningHour::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PlaceImage::class);
    }

    public function managers(): HasMany
    {
        return $this->hasMany(PlaceManager::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function visitEvents(): HasMany
    {
        return $this->hasMany(VisitEvent::class);
    }

    public function anonymousVisitEvents(): HasMany
    {
        return $this->hasMany(AnonymousVisitEvent::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function placeTags(): HasMany
    {
        return $this->hasMany(PlaceTag::class);
    }

    public function promotionRequests(): HasMany
    {
        return $this->hasMany(PromotionRequest::class);
    }
}
