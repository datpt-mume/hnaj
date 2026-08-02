<?php

namespace Tests\Feature\Auth;

use App\Actions\Auth\CreateAdminAccount;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use DomainException;
use Illuminate\Support\Facades\Hash;

class CreateAdminAccountTest extends AuthTestCase
{
    public function test_it_creates_the_system_admin_with_normalised_identity(): void
    {
        $user = app(CreateAdminAccount::class)->handle(
            '  SYSTEM.ADMIN  ',
            '  System Admin  ',
            '  ADMIN@EXAMPLE.COM  ',
            'Password123',
        );

        $this->assertSame('system.admin', $user->username);
        $this->assertSame('admin@example.com', $user->email);
        $this->assertSame('System Admin', $user->name);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame([RoleName::Admin->value], $user->roleNames());
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_a_second_bootstrap_when_admin_already_exists(): void
    {
        // Bootstrap the first admin.
        app(CreateAdminAccount::class)->handle(
            'system.admin',
            'System Admin',
            'admin@example.com',
            'Password123',
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The system administrator has already been created.');

        app(CreateAdminAccount::class)->handle(
            'another.admin',
            'Another Admin',
            'another@example.com',
            'AnotherPassword123',
        );

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_username_already_in_use(): void
    {
        $this->createUserWithRole(RoleName::User, [
            'username' => 'existing.user',
            'email' => 'existing@example.com',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The username is already in use.');

        app(CreateAdminAccount::class)->handle(
            'existing.user',
            'New Admin',
            'new@example.com',
            'Password123',
        );

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_email_already_in_use(): void
    {
        $this->createUserWithRole(RoleName::User, [
            'username' => 'email.owner',
            'email' => 'owned@example.com',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The email address is already in use.');

        app(CreateAdminAccount::class)->handle(
            'new.admin',
            'New Admin',
            'owned@example.com',
            'Password123',
        );

        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_rejects_when_both_username_and_email_are_taken_by_different_accounts(): void
    {
        $this->createUserWithRole(RoleName::User, [
            'username' => 'taken.user',
            'email' => 'taken@example.com',
        ]);
        $this->createUserWithRole(RoleName::User, [
            'username' => 'other.user',
            'email' => 'other@example.com',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('The username is already in use.');

        app(CreateAdminAccount::class)->handle(
            'taken.user',
            'New Admin',
            'other@example.com',
            'Password123',
        );

        $this->assertDatabaseCount('users', 2);
    }
}
