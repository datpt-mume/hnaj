<?php

namespace App\Models;

use App\Enums\ManagerApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerApplication extends Model
{
    protected $fillable = [
        'place_request_id',
        'place_id',
        'user_id',
        'email',
        'representative_name',
        'proof_reference',
        'status',
        'approved_user_id',
        'reviewed_by',
        'reviewed_at',
        'review_reason',
    ];

    protected $casts = [
        'status' => ManagerApplicationStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function placeRequest(): BelongsTo
    {
        return $this->belongsTo(PlaceRequest::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
