<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnonymousVisitEvent extends Model
{
    protected $fillable = [
        'place_id',
        'anonymous_key_hash',
        'visit_date',
        'visited_at',
        'source',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visited_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
