<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlaceImage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'place_id',
        'uploaded_by',
        'image_url',
        'alt_text',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function placesAsThumbnail(): HasMany
    {
        return $this->hasMany(Place::class, 'thumbnail_image_id');
    }
}
