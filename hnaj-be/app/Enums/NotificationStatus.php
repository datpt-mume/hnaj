<?php

namespace App\Enums;

/**
 * Trạng thái gửi email thông báo.
 */
enum NotificationStatus: string
{
    case Pending = 'pending'; // Đã yêu cầu gửi, đang chờ trong queue
    case Sent = 'sent';       // Gửi thành công
    case Failed = 'failed';   // Gửi thất bại, kèm lý do trong failure_reason
}
