<?php

namespace App\Models;

use App\Enums\ScheduleType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceOpeningHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'day_of_week',
        'schedule_type',
        'opens_at',
        'closes_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'schedule_type' => ScheduleType::class,
    ];

    protected function opensAt(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5),
            set: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5).':00',
        );
    }

    protected function closesAt(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5),
            set: static fn (?string $value): ?string => $value === null ? null : substr($value, 0, 5).':00',
        );
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
