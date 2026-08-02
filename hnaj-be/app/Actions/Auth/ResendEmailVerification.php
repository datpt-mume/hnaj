<?php

namespace App\Actions\Auth;

use App\Repositories\UserRepository;

/**
 * Gửi lại email xác thực.
 * Không tiết lộ email có tồn tại hay không để tránh dò tài khoản.
 */
class ResendEmailVerification
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly IssueEmailVerificationToken $issueToken,
    ) {}

    public function handle(string $email): void
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return;
        }

        if ($user->hasVerifiedEmailAddress() || ! $user->isActive()) {
            return;
        }

        $this->issueToken->handle($user);
    }
}
