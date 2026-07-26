<?php

namespace App\Models;

use App\Enums\ModerationAction as ModerationActionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModerationAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'performed_by',
        'target_type',
        'target_id',
        'action',
        'reason',
        'metadata',
    ];

    protected $casts = [
        'action' => ModerationActionEnum::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
