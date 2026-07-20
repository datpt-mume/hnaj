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
        'invalid_rows', 'processed_rows', 'imported_rows', 'skipped_rows', 'failed_rows',
        'progress_percent', 'error_message', 'confirmed_at', 'started_at', 'paused_at',
        'progress_updated_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'paused_at' => 'datetime',
            'progress_updated_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'duplicate_rows' => 'integer',
            'invalid_rows' => 'integer',
            'processed_rows' => 'integer',
            'imported_rows' => 'integer',
            'skipped_rows' => 'integer',
            'failed_rows' => 'integer',
            'progress_percent' => 'integer',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PlaceImportRow::class, 'batch_id');
    }
}
