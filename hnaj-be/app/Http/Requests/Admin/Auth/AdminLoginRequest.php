<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\Auth\LoginRequest;

/**
 * Khu vực quản trị dùng endpoint đăng nhập riêng nhưng cùng bộ input với người dùng thường.
 */
class AdminLoginRequest extends LoginRequest
{
}
