<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceTag extends Model
{
    protected $fillable = ['place_id', 'tag_id'];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }
}
