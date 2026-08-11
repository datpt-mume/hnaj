<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Place;
use Tests\Feature\Auth\AuthTestCase;

class AdminPlaceDeleteTest extends AuthTestCase
{
    public function test_admin_can_hard_delete_place_without_request_body(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->unverified()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/places/'.$place->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Đã xóa địa điểm.');

        $this->assertDatabaseMissing('places', ['id' => $place->id]);
    }

    public function test_hard_delete_still_requires_admin_authorization(): void
    {
        $place = Place::factory()->unverified()->create();

        $this->deleteJson('/api/admin/places/'.$place->id)
            ->assertUnauthorized();

        $this->assertDatabaseHas('places', ['id' => $place->id]);
    }
}
