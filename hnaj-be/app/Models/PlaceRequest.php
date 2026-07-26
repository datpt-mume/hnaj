<?php

namespace App\Models;

use App\Enums\PlaceRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlaceRequest extends Model
{
    protected $fillable = [
        'submitted_by',
        'place_id',
        'name_input',
        'google_maps_url_input',
        'address_text_input',
        'category_id_input',
        'source_image_path',
        'normalized_data',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected $casts = [
        'normalized_data' => 'array',
        'status' => PlaceRequestStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function categoryInput(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id_input');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function managerApplication(): HasOne
    {
        return $this->hasOne(ManagerApplication::class);
    }
}
