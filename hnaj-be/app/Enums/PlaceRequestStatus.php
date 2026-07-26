<?php

namespace App\Enums;

/**
 * Trạng thái yêu cầu thêm địa điểm do User gửi.
 */
enum PlaceRequestStatus: string
{
    case Pending = 'pending';     // Chờ Admin xem xét và chuẩn hóa
    case Approved = 'approved';   // Admin đã duyệt, place đã được tạo/kích hoạt
    case Rejected = 'rejected';   // Admin từ chối, kèm lý do trong review_reason
}
