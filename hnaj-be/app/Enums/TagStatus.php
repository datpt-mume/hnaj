<?php

namespace App\Enums;

/**
 * Trạng thái tag.
 */
enum TagStatus: string
{
    case Active = 'active';     // Tag đang hoạt động
    case Inactive = 'inactive'; // Tag đã ẩn, không cho gán mới
}
