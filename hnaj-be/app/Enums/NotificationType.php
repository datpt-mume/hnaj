<?php

namespace App\Enums;

/**
 * Loại email thông báo nghiệp vụ gửi cho người dùng hoặc ứng viên.
 */
enum NotificationType: string
{
    case RequestApproved = 'request_approved'; // Thông báo place request hoặc manager application đã được duyệt
    case RequestRejected = 'request_rejected'; // Thông báo place request hoặc manager application bị từ chối
    case AccountSetup = 'account_setup';       // Email chứa link đặt password lần đầu cho Sub-admin mới
}
