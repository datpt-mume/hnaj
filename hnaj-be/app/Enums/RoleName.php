<?php

namespace App\Enums;

/**
 * Tên role hệ thống, dùng trong bảng roles.
 */
enum RoleName: string
{
    case User = 'user';           // Người dùng thông thường, có thể bookmark, review, comment
    case SubAdmin = 'sub_admin';  // Quản lý địa điểm, có quyền quản lý place được gán
    case Admin = 'admin';         // Quản trị viên, có toàn quyền hệ thống
}
