<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PlaceImportBatch extends Model
{
    use HasUlids;

    protected $fillable = [
        'filename', 'status', 'total_rows', 'valid_rows', 'duplicate_rows',
        'invalid_rows', 'error_message', 'confirmed_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'duplicate_rows' => 'integer',
            'invalid_rows' => 'integer',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PlaceImportRow::class, 'batch_id');
    }
}
