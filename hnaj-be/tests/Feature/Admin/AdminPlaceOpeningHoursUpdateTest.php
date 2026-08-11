<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Place;
use Tests\Feature\Auth\AuthTestCase;

class AdminPlaceOpeningHoursUpdateTest extends AuthTestCase
{
    public function test_admin_can_update_all_stored_weekdays_including_sunday(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->unverified()->create();

        $openingHours = array_map(
            static fn (int $dayOfWeek): array => [
                'day_of_week' => $dayOfWeek,
                'schedule_type' => 'regular',
                'opens_at' => '08:00',
                'closes_at' => '22:00',
            ],
            range(2, 8),
        );

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/places/'.$place->id, [
                'name' => $place->name,
                'address_text' => $place->address_text,
                'district_id' => $place->district_id,
                'category_id' => $place->category_id,
                'tag_ids' => [],
                'phone' => $place->phone,
                'website_url' => null,
                'google_maps_url' => $place->google_maps_url,
                'google_place_id' => $place->google_place_id,
                'latitude' => (float) $place->latitude,
                'longitude' => (float) $place->longitude,
                'min_price' => null,
                'max_price' => null,
                'description' => null,
                'status' => 'active',
                'opening_hours' => $openingHours,
                'images' => [],
                'thumbnail_image_id' => null,
                'deleted_image_ids' => [],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.opening_hours.6.day_of_week', 8);

        $this->assertDatabaseHas('place_opening_hours', [
            'place_id' => $place->id,
            'day_of_week' => 8,
            'schedule_type' => 'regular',
            'opens_at' => '08:00:00',
            'closes_at' => '22:00:00',
        ]);
    }

    public function test_admin_place_update_rejects_day_outside_stored_convention(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->unverified()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/places/'.$place->id, [
                'opening_hours' => [[
                    'day_of_week' => 1,
                    'schedule_type' => 'closed',
                    'opens_at' => null,
                    'closes_at' => null,
                ]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['opening_hours', 'opening_hours.0.day_of_week']);
    }
}
