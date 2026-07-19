<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlaceExternalId extends Model
{
    use HasUlids;

    protected $fillable = ['place_id', 'provider', 'external_id', 'fingerprint'];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
