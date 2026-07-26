<?php

namespace App\Enums;

/**
 * Trạng thái yêu cầu quảng bá địa điểm do Sub-admin gửi.
 */
enum PromotionRequestStatus: string
{
    case Pending = 'pending';     // Chờ Admin xem xét
    case Approved = 'approved';   // Admin ghi nhận yêu cầu quảng bá
    case Rejected = 'rejected';   // Admin từ chối
    case Cancelled = 'cancelled'; // Sub-admin tự hủy yêu cầu
}
