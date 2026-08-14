<?php

namespace Tests\Feature\Admin;

use App\Enums\ManagerApplicationStatus;
use App\Enums\RoleName;
use App\Models\ManagerApplication;
use App\Models\Place;
use Tests\Feature\Auth\AuthTestCase;

class AdminManagerApplicationTest extends AuthTestCase
{
    public function test_user_can_apply_to_manage_existing_place(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = Place::factory()->verified()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/manager-applications', ['place_id' => $place->id])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('manager_applications', [
            'place_id' => $place->id,
            'user_id' => $user->id,
            'status' => ManagerApplicationStatus::Pending->value,
        ]);
    }

    public function test_admin_can_approve_application_and_assign_sub_admin_role(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $applicant = $this->createUserWithRole(RoleName::User);
        $place = Place::factory()->verified()->create();

        $application = ManagerApplication::query()->create([
            'place_id' => $place->id,
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'representative_name' => $applicant->name,
            'status' => ManagerApplicationStatus::Pending,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/manager-applications/'.$application->id.'/approve')
            ->assertOk()
            ->assertJsonPath('success', true);

        $applicant->refresh();

        $this->assertContains(RoleName::SubAdmin->value, $applicant->roleNames());
        $this->assertContains(RoleName::User->value, $applicant->roleNames());
        $this->assertDatabaseHas('place_managers', ['place_id' => $place->id, 'user_id' => $applicant->id]);
        $this->assertSame(ManagerApplicationStatus::Approved, $application->refresh()->status);
    }

    public function test_admin_can_reject_application_with_reason(): void
    {
        $admin = $this->createUserWithRole(RoleName::Admin);
        $applicant = $this->createUserWithRole(RoleName::User);
        $place = Place::factory()->verified()->create();

        $application = ManagerApplication::query()->create([
            'place_id' => $place->id,
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'representative_name' => $applicant->name,
            'status' => ManagerApplicationStatus::Pending,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/manager-applications/'.$application->id.'/reject', [
                'reason' => 'Chưa đủ thông tin.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(ManagerApplicationStatus::Rejected, $application->refresh()->status);
        $this->assertSame('Chưa đủ thông tin.', $application->review_reason);
    }
}
