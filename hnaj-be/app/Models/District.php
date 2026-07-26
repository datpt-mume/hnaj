<?php

namespace App\Models;

use App\Enums\DistrictStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class District extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'status'];

    protected $casts = [
        'status' => DistrictStatus::class,
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
