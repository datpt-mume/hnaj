<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Exceptions\AuthFlowException;
use App\Models\User;

/**
 * Đăng nhập cho khu vực quản trị.
 * Dùng endpoint riêng và bắt buộc tài khoản có role admin.
 */
class LoginAdmin
{
    public function __construct(
        private readonly AuthenticateCredentials $authenticate,
        private readonly IssueAccessToken $issueAccessToken,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function handle(string $username, string $password): array
    {
        $user = $this->authenticate->handle($username, $password);

        if (! $user->hasRole(RoleName::Admin)) {
            throw AuthFlowException::forbiddenRole();
        }

        return [
            'user' => $user,
            'token' => $this->issueAccessToken->handle($user, 'admin'),
        ];
    }
}
