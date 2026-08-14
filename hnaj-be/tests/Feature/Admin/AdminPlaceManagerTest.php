<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\Place;
use App\Models\PlaceManager;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Auth\AuthTestCase;

class AdminPlaceManagerTest extends AuthTestCase
{
    public function test_admin_can_create_place_manager_and_sends_setup_mail(): void
    {
        Mail::fake();

        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->verified()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/places/'.$place->id.'/managers', [
                'username' => 'manager.one',
                'email' => 'manager.one@example.com',
                'password' => 'Password123',
                'full_name' => 'Manager One',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', ['username' => 'manager.one']);
        $this->assertDatabaseHas('place_managers', ['place_id' => $place->id]);
        Mail::assertSent(\App\Mail\AccountSetupMail::class);
    }

    public function test_create_place_manager_rejects_duplicate_username(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->verified()->create();
        User::factory()->create(['username' => 'taken.manager']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/places/'.$place->id.'/managers', [
                'username' => 'taken.manager',
                'email' => 'manager.two@example.com',
                'password' => 'Password123',
            ])
            ->assertUnprocessable();
    }

    public function test_admin_can_revoke_place_manager(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->verified()->create();
        $manager = $this->createUserWithRole(RoleName::SubAdmin);

        PlaceManager::query()->create([
            'place_id' => $place->id,
            'user_id' => $manager->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/places/'.$place->id.'/managers/'.$manager->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotNull(
            PlaceManager::query()->where('place_id', $place->id)->where('user_id', $manager->id)->first()->revoked_at,
        );
    }

    public function test_admin_can_list_place_managers(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $place = Place::factory()->verified()->create();
        $manager = $this->createUserWithRole(RoleName::SubAdmin);

        PlaceManager::query()->create([
            'place_id' => $place->id,
            'user_id' => $manager->id,
            'assigned_by' => $admin->id,
            'assigned_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/places/'.$place->id.'/managers')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
