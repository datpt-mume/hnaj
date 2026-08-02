<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Models\Role;

class RoleRepository
{
    public function findByName(RoleName $name): ?Role
    {
        return Role::query()->where('name', $name->value)->first();
    }

    public function findByNameOrFail(RoleName $name): Role
    {
        return Role::query()->where('name', $name->value)->firstOrFail();
    }
}
