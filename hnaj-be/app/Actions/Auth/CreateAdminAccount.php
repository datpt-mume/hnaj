<?php

namespace App\Actions\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Bootstrap tài khoản admin hệ thống đầu tiên và duy nhất qua tinker.
 * Chỉ cho phép tạo một lần duy nhất; tất cả các lần gọi sau đều bị từ chối.
 * Không seed credential vào source control.
 */
class CreateAdminAccount
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly RoleRepository $roles,
    ) {}

    /**
     * Tạo duy nhất một tài khoản admin hệ thống.
     *
     * @throws DomainException khi đã có admin, hoặc username/email đã tồn tại.
     */
    public function handle(
        string $username,
        string $fullName,
        string $email,
        string $password,
    ): User {
        $username = mb_strtolower(trim($username));
        $email = mb_strtolower(trim($email));
        $fullName = trim($fullName);

        return DB::transaction(function () use ($username, $fullName, $email, $password): User {
            if ($this->users->adminExists()) {
                throw new DomainException('The system administrator has already been created.');
            }

            if ($this->users->findByUsername($username) !== null) {
                throw new DomainException('The username is already in use.');
            }

            if ($this->users->findByEmail($email) !== null) {
                throw new DomainException('The email address is already in use.');
            }

            $user = $this->users->create([
                'name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'status' => UserStatus::Active,
            ]);

            $this->users->markEmailVerified($user);
            $this->users->assignRole($user, $this->roles->findByNameOrFail(RoleName::Admin));

            return $user->load('roles');
        });
    }
}
