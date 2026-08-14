<?php

namespace Tests\Feature\Auth;

use App\Enums\RoleName;

class RoleMiddlewareTest extends AuthTestCase
{
    public function test_admin_can_access_admin_me(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/auth/me')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => ['roles' => [RoleName::Admin->value]],
                ],
            ]);
    }

    public function test_regular_user_can_access_user_me(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => ['roles' => [RoleName::User->value]],
                ],
            ]);
    }

    public function test_admin_is_forbidden_from_user_me(): void
    {
        $account = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($account, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN_ROLE',
            ]);
    }

    public function test_sub_admin_can_access_user_me(): void
    {
        $account = $this->createUserWithRole(RoleName::SubAdmin);

        $this->actingAs($account, 'sanctum')
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => ['roles' => [RoleName::SubAdmin->value]],
                ],
            ]);
    }

    public function test_regular_user_is_forbidden_from_admin_routes(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/auth/me')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN_ROLE',
            ]);
    }

    public function test_sub_admin_is_forbidden_from_system_admin_routes(): void
    {
        $subAdmin = $this->createUserWithRole(RoleName::SubAdmin);

        $this->actingAs($subAdmin, 'sanctum')
            ->getJson('/api/admin/auth/me')
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'code' => 'FORBIDDEN_ROLE',
            ]);
    }

    public function test_admin_route_requires_authentication(): void
    {
        $this->getJson('/api/admin/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_role_revocation_takes_effect_for_an_existing_token(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $plainToken = $admin->createToken('admin')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/admin/auth/me')
            ->assertOk();

        $admin->roles()->detach();
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->getJson('/api/admin/auth/me')
            ->assertStatus(403)
            ->assertJson(['code' => 'FORBIDDEN_ROLE']);
    }

    public function test_admin_logout_revokes_the_current_token(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $plainToken = $admin->createToken('admin')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$plainToken)
            ->postJson('/api/admin/auth/logout')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
