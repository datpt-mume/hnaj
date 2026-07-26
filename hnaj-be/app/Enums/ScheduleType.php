<?php

namespace App\Enums;

/**
 * Loại khung giờ mở cửa trong ngày.
 */
enum ScheduleType: string
{
    case Regular = 'regular'; // Có giờ mở/đóng cụ thể, cần opens_at và closes_at
    case AllDay = 'all_day';  // Mở cả ngày 24h, không cần giờ mở/đóng
    case Closed = 'closed';   // Đóng cửa cả ngày, không cần giờ mở/đóng
}
