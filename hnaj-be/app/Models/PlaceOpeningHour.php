<?php

namespace App\Models;

use App\Enums\ScheduleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceOpeningHour extends Model
{
    protected $fillable = [
        'place_id',
        'day_of_week',
        'schedule_type',
        'opens_at',
        'closes_at',
        'crosses_midnight',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'schedule_type' => ScheduleType::class,
        'opens_at' => 'datetime:H:i',
        'closes_at' => 'datetime:H:i',
        'crosses_midnight' => 'boolean',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
