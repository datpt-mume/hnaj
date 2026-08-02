<?php

namespace App\Enums;

/**
 * Mã lỗi ổn định cho các luồng authentication, dùng trong error envelope.
 */
enum AuthErrorCode: string
{
    case InvalidCredentials = 'INVALID_CREDENTIALS';   // Sai username hoặc password
    case EmailNotVerified = 'EMAIL_NOT_VERIFIED';      // Chưa xác thực email nên chưa hoàn tất đăng ký
    case AccountNotActive = 'ACCOUNT_NOT_ACTIVE';      // Tài khoản bị suspended hoặc disabled
    case InvalidToken = 'INVALID_VERIFICATION_TOKEN';  // Token verify sai, đã dùng hoặc hết hạn
    case AlreadyVerified = 'EMAIL_ALREADY_VERIFIED';   // Email đã được xác thực trước đó
    case Unauthenticated = 'UNAUTHENTICATED';          // Thiếu hoặc sai bearer token
    case ForbiddenRole = 'FORBIDDEN_ROLE';             // Thiếu role bắt buộc cho endpoint
    case GoogleAuthFailed = 'GOOGLE_AUTH_FAILED';      // Không hoàn tất được OAuth với Google
}
