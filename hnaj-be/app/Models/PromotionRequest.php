<?php

namespace App\Models;

use App\Enums\PromotionRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRequest extends Model
{
    protected $fillable = [
        'place_id',
        'submitted_by',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected $casts = [
        'status' => PromotionRequestStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
