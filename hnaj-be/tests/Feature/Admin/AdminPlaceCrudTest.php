<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use Tests\Feature\Auth\AuthTestCase;

class AdminPlaceCrudTest extends AuthTestCase
{
    public function test_admin_can_list_all_places_with_filters(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        Place::factory()->verified()->count(2)->create();
        Place::factory()->unverified()->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/places?per_page=10')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_place(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $district = District::factory()->create();
        $category = Category::factory()->create();

        $payload = [
            'name' => 'Quán mới',
            'address_text' => '123 Đường Láng',
            'district_id' => $district->id,
            'category_id' => $category->id,
            'google_maps_url' => 'https://maps.google.com/?q=21.0,105.8',
            'latitude' => 21.0,
            'longitude' => 105.8,
            'status' => 'active',
        ];

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/places', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_verified', true);

        $this->assertDatabaseHas('places', ['name' => 'Quán mới', 'is_verified' => true]);
    }

    public function test_create_place_requires_admin_role(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $district = District::factory()->create();
        $category = Category::factory()->create();

        $payload = [
            'name' => 'Quán mới',
            'address_text' => '123 Đường Láng',
            'district_id' => $district->id,
            'category_id' => $category->id,
            'google_maps_url' => 'https://maps.google.com/?q=21.0,105.8',
            'latitude' => 21.0,
            'longitude' => 105.8,
            'status' => 'active',
        ];

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/places', $payload)
            ->assertForbidden();
    }
}
