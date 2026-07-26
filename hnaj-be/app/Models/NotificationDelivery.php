<?php

namespace App\Models;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDelivery extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_email',
        'notifiable_type',
        'notifiable_id',
        'notification_type',
        'status',
        'sent_at',
        'failure_reason',
    ];

    protected $casts = [
        'notification_type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
