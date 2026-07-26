<?php

namespace App\Enums;

/**
 * Trạng thái danh mục địa điểm.
 */
enum CategoryStatus: string
{
    case Active = 'active';     // Danh mục đang hoạt động
    case Inactive = 'inactive'; // Danh mục đã ẩn, không cho gán mới
}
