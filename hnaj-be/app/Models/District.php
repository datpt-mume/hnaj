<?php

namespace App\Models;

use App\Enums\DistrictStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'status'];

    protected $casts = [
        'status' => DistrictStatus::class,
    ];

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
