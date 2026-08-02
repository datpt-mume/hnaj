<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

/**
 * Base cho các test authentication: seed role hệ thống và cung cấp helper tạo user.
 */
abstract class AuthTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createUserWithRole(
        RoleName $role = RoleName::User,
        array $attributes = [],
    ): User {
        $user = User::factory()->create(array_merge([
            'status' => UserStatus::Active,
            'email_verified_at' => Carbon::now(),
        ], $attributes));

        $user->roles()->attach(
            Role::query()->where('name', $role->value)->value('id'),
            ['assigned_at' => Carbon::now()],
        );

        return $user->load('roles');
    }
}
