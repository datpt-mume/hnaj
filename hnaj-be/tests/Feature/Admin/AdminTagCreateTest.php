<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Enums\TagStatus;
use App\Models\Tag;
use Tests\Feature\Auth\AuthTestCase;

class AdminTagCreateTest extends AuthTestCase
{
    public function test_admin_can_create_tag(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => '  Ăn khuya  '])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Tag created successfully.')
            ->assertJsonPath('data.name', 'Ăn khuya')
            ->assertJsonPath('data.slug', 'an-khuya');

        $this->assertDatabaseHas('tags', [
            'name' => 'Ăn khuya',
            'slug' => 'an-khuya',
            'status' => TagStatus::Active->value,
        ]);
    }

    public function test_create_tag_requires_unique_name(): void
    {
        Tag::factory()->create(['name' => 'Chill', 'slug' => 'chill']);
        $admin = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => 'Chill'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_tag_requires_non_empty_name(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_create_tag_requires_admin_authentication(): void
    {
        $this->postJson('/api/admin/tags', ['name' => 'Ăn nhanh'])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_regular_user_cannot_create_tag(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/tags', ['name' => 'Ăn nhanh'])
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }
}
