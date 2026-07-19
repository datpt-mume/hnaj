<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminPlaceImportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_import_preview(): void
    {
        $response = $this->postJson('/api/v1/admin/imports/places/preview');

        $response->assertUnauthorized();
    }

    public function test_user_role_cannot_access_import_preview(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/v1/admin/imports/places/preview');

        $response->assertForbidden();
    }

    public function test_editor_reaches_file_validation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('editor');

        $response = $this->actingAs($user)->postJson('/api/v1/admin/imports/places/preview');

        $response->assertUnprocessable();
    }
}
