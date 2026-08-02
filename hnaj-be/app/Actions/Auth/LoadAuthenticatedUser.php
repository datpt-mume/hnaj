<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Repositories\UserRepository;

class LoadAuthenticatedUser
{
    public function __construct(private readonly UserRepository $users) {}

    public function handle(User $user): User
    {
        return $this->users->loadRoles($user);
    }
}
