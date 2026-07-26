<?php

namespace App\Enums;

/**
 * Trạng thái tài khoản người dùng.
 */
enum UserStatus: string
{
    case Active = 'active';       // Tài khoản hoạt động bình thường
    case Suspended = 'suspended'; // Tạm khóa, người dùng không thể đăng nhập
    case Disabled = 'disabled';   // Vô hiệu hóa vĩnh viễn
}
