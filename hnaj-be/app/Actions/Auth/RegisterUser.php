<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;

/**
 * Đăng ký người dùng thường.
 * Tài khoản được tạo ở trạng thái chưa xác thực email và chưa thể đăng nhập
 * cho tới khi bấm liên kết trong email xác thực.
 */
class RegisterUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
        private readonly IssueEmailVerificationToken $issueToken,
    ) {}

    /**
     * @param  array{username: string, full_name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = $this->users->create([
                'name' => $data['full_name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => UserStatus::Active,
            ]);

            $this->users->assignRole($user, $this->roles->findByNameOrFail(RoleName::User));
            $this->issueToken->handle($user);

            return $user->load('roles');
        });
    }
}
