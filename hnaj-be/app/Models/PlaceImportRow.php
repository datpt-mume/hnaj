<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlaceImportRow extends Model
{
    use HasUlids;

    protected $fillable = [
        'batch_id', 'row_number', 'external_id', 'fingerprint', 'status',
        'normalized_data', 'classification', 'errors',
        'processed_at', 'attempts',
    ];

    protected function casts(): array
    {
        return [
            'normalized_data' => 'array',
            'classification' => 'array',
            'errors' => 'array',
            'row_number' => 'integer',
            'processed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PlaceImportBatch::class, 'batch_id');
    }
}
