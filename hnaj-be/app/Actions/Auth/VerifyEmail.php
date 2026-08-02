<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

/**
 * Xác thực email bằng token dùng một lần, hoàn tất quá trình đăng ký.
 */
class VerifyEmail
{
    public function __construct(
        private readonly EmailVerificationTokenRepository $tokens,
        private readonly UserRepository $users,
    ) {}

    public function handle(string $plainToken): User
    {
        return DB::transaction(function () use ($plainToken): User {
            $token = $this->tokens->lockUsableByHash(hash('sha256', $plainToken));

            if ($token === null) {
                throw AuthFlowException::invalidVerificationToken();
            }

            $user = $token->user;

            if ($user->hasVerifiedEmailAddress()) {
                throw AuthFlowException::emailAlreadyVerified();
            }

            $this->tokens->markUsed($token);
            $this->users->markEmailVerified($user);

            return $user->load('roles');
        });
    }
}
