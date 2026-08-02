<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;

class UserRepository
{
    public function findById(int $id): ?User
    {
        return User::query()->with('roles')->find($id);
    }

    public function findByUsername(string $username): ?User
    {
        return User::query()
            ->with('roles')
            ->where('username', $username)
            ->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->with('roles')->where('email', $email)->first();
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return User::query()->with('roles')->where('google_id', $googleId)->first();
    }

    public function usernameExists(string $username): bool
    {
        return User::query()->where('username', $username)->exists();
    }

    /**
     * Kiểm tra hệ thống đã có ít nhất một user mang role admin hay chưa.
     * Dùng trong bootstrap admin one-time create-only.
     */
    public function adminExists(): bool
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes);
        $user->save();

        return $user;
    }

    public function markEmailVerified(User $user): User
    {
        $user->email_verified_at = Carbon::now();
        $user->save();

        return $user;
    }

    public function loadRoles(User $user): User
    {
        return $user->loadMissing('roles');
    }

    /**
     * Gán role cho user, bỏ qua nếu đã có để thao tác lặp lại an toàn.
     */
    public function assignRole(User $user, Role $role, ?int $assignedBy = null): void
    {
        if ($user->roles()->where('roles.id', $role->id)->exists()) {
            return;
        }

        $user->roles()->attach($role->id, [
            'assigned_by' => $assignedBy,
            'assigned_at' => Carbon::now(),
        ]);

        $user->unsetRelation('roles');
    }
}
