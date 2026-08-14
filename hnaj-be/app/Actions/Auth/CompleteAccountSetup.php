<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Repositories\AccountSetupTokenRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

/**
 * Hoàn tất setup tài khoản Sub-admin: đặt password mới, xác thực email và kích hoạt.
 * Token dùng một lần, 24 giờ; database chỉ lưu hash.
 */
class CompleteAccountSetup
{
    public function __construct(
        private readonly AccountSetupTokenRepository $tokens,
        private readonly UserRepository $users,
    ) {}

    public function handle(string $plainToken, string $newPassword): User
    {
        return DB::transaction(function () use ($plainToken, $newPassword): User {
            $token = $this->tokens->lockUsableByHash(hash('sha256', $plainToken));

            if ($token === null) {
                throw AuthFlowException::invalidVerificationToken();
            }

            $user = $token->user;

            $this->tokens->markUsed($token);
            $this->users->update($user, ['password' => $newPassword]);
            $this->users->markEmailVerified($user);

            return $user->load('roles');
        });
    }
}
