<?php

namespace App\Enums;

/**
 * Trạng thái quận/huyện/thị xã.
 */
enum DistrictStatus: string
{
    case Active = 'active';     // Đang sử dụng, có thể gán cho place
    case Inactive = 'inactive'; // Không còn sử dụng, không cho gán mới
}
