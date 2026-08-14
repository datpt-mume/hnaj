<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\UserRepository;

class UpdateProfile
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(User $user, string $fullName): User
    {
        $this->users->update($user, ['name' => $fullName]);

        return $this->users->loadRoles($user);
    }
}