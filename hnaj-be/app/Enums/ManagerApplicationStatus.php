<?php

namespace App\Enums;

/**
 * Trạng thái đơn xin làm Sub-admin quản lý place.
 */
enum ManagerApplicationStatus: string
{
    case Pending = 'pending';   // Chờ Admin duyệt place và role
    case Approved = 'approved'; // Đã duyệt, tạo account và gửi email đặt password
    case Rejected = 'rejected'; // Bị từ chối, kèm lý do
}
