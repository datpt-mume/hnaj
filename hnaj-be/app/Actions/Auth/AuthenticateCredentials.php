<?php

namespace App\Actions\Auth;

use App\Exceptions\AuthFlowException;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

/**
 * Kiểm tra username + password và các điều kiện tài khoản dùng chung
 * cho cả đăng nhập người dùng thường và đăng nhập admin.
 */
class AuthenticateCredentials
{
    /**
     * Hash của một password không dùng được, chỉ để chạy Hash::check khi không tìm
     * thấy user nhằm giảm khác biệt thời gian phản hồi giữa username tồn tại và không.
     */
    private const DUMMY_HASH = '$2y$12$Rz7Yq4gK5jFQZ2oJ1uV3pOgnZxJ8Kf1lQ6wYbT0sN9eXhH2mC4uCa';

    public function __construct(
        private readonly UserRepository $users,
    ) {}

    public function handle(string $username, string $password): User
    {
        $user = $this->users->findByUsername($username);

        $passwordMatches = Hash::check($password, $user?->password ?? self::DUMMY_HASH);

        if ($user === null || ! $passwordMatches) {
            throw AuthFlowException::invalidCredentials();
        }

        if (! $user->isActive()) {
            throw AuthFlowException::accountNotActive();
        }

        if (! $user->hasVerifiedEmailAddress()) {
            throw AuthFlowException::emailNotVerified();
        }

        return $user;
    }
}
