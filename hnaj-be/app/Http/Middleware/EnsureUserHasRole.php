<?php

namespace App\Http\Middleware;

use App\Enums\AuthErrorCode;
use App\Enums\RoleName;
use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn request nếu tài khoản không có role bắt buộc.
 *
 * Role được đọc từ database tại thời điểm request, không lấy từ ability của token,
 * để việc Admin thu hồi role có hiệu lực ngay với token đã phát hành.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::error(
                message: 'Authentication is required to access this resource.',
                code: AuthErrorCode::Unauthenticated->value,
                status: 401,
            );
        }

        $required = array_map(
            static fn (string $role): RoleName => RoleName::from($role),
            $roles,
        );

        $user->loadMissing('roles');

        if (! $user->hasAnyRole(...$required)) {
            return ApiResponse::error(
                message: 'You do not have permission to access this resource.',
                code: AuthErrorCode::ForbiddenRole->value,
                status: 403,
            );
        }

        return $next($request);
    }
}
