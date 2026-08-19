<?php

namespace Tests\Feature\Visit;

use App\Enums\PlaceStatus;
use App\Enums\RoleName;
use App\Models\AnonymousVisitEvent;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\User;
use App\Models\VisitEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Feature test cho API ghi nhận và lịch sử "Đi tới đó".
 *
 * Chạy trên MySQL dùng database riêng `hnaj_test` theo phpunit.xml;
 * RefreshDatabase gọi migrate:fresh mỗi lần chạy.
 */
class VisitTest extends AuthTestCase
{
    use RefreshDatabase;

    private District $district;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->district = District::factory()->create(['name' => 'Quận A']);
        $this->category = Category::factory()->create(['slug' => 'danh-muc-a']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPlace(array $overrides = []): Place
    {
        return Place::factory()->create(array_merge([
            'district_id' => $this->district->id,
            'category_id' => $this->category->id,
            'status' => PlaceStatus::Active,
            'is_verified' => true,
        ], $overrides));
    }

    private function actingAsUser(?User $user = null): void
    {
        Sanctum::actingAs($user ?? $this->createUserWithRole(RoleName::User), ['*']);
    }

    public function test_guest_without_anonymous_id_returns_422(): void
    {
        $place = $this->createPlace();

        $this->postJson('/api/visits', ['place_id' => $place->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VISIT_ANONYMOUS_KEY_REQUIRED');
    }

    public function test_guest_with_anonymous_id_creates_anonymous_visit(): void
    {
        $place = $this->createPlace();

        $response = $this->postJson('/api/visits', ['place_id' => $place->id], [
            'X-Anonymous-Id' => 'guest-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.place_id', $place->id)
            ->assertJsonPath('data.anonymous', true)
            ->assertJsonPath('data.created', true)
            ->assertJsonPath('data.id', null);

        $hash = hash('sha256', 'guest-123');

        $this->assertDatabaseHas('anonymous_visit_events', [
            'place_id' => $place->id,
            'anonymous_key_hash' => $hash,
        ]);

        $this->assertDatabaseMissing('anonymous_visit_events', [
            'anonymous_key_hash' => 'guest-123',
        ]);
    }

    public function test_guest_same_day_same_place_is_idempotent(): void
    {
        Carbon::setTestNow('2026-08-19 08:00:00');

        $place = $this->createPlace();

        $first = $this->postJson('/api/visits', ['place_id' => $place->id], [
            'X-Anonymous-Id' => 'guest-123',
        ]);

        $second = $this->postJson('/api/visits', ['place_id' => $place->id], [
            'X-Anonymous-Id' => 'guest-123',
        ]);

        $first->assertCreated();
        $second->assertOk()
            ->assertJsonPath('data.created', false);

        $this->assertSame(1, AnonymousVisitEvent::query()->count());
    }

    public function test_authenticated_user_records_visit_ignoring_anonymous_header(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $response = $this->postJson('/api/visits', ['place_id' => $place->id], [
            'X-Anonymous-Id' => 'ignored-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.anonymous', false)
            ->assertJsonPath('data.id', fn ($id) => $id !== null);

        $this->assertDatabaseHas('visit_events', [
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);

        $this->assertSame(0, AnonymousVisitEvent::query()->count());
    }

    public function test_user_same_day_same_place_is_idempotent(): void
    {
        Carbon::setTestNow('2026-08-19 08:00:00');

        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $this->postJson('/api/visits', ['place_id' => $place->id])->assertCreated();
        $this->postJson('/api/visits', ['place_id' => $place->id])->assertOk();

        $this->assertSame(1, VisitEvent::query()->count());
    }

    public function test_user_new_day_creates_new_visit(): void
    {
        Carbon::setTestNow('2026-08-19 08:00:00');

        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $this->postJson('/api/visits', ['place_id' => $place->id])->assertCreated();

        Carbon::setTestNow('2026-08-20 08:00:00');

        $this->postJson('/api/visits', ['place_id' => $place->id])->assertCreated();

        $this->assertSame(2, VisitEvent::query()->count());
    }

    public function test_hidden_place_returns_404(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $hidden = $this->createPlace(['status' => PlaceStatus::Hidden]);
        $this->actingAsUser($user);

        $this->postJson('/api/visits', ['place_id' => $hidden->id])
            ->assertNotFound()
            ->assertJsonPath('code', 'VISIT_PLACE_NOT_AVAILABLE');
    }

    public function test_unverified_place_returns_404(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $unverified = $this->createPlace(['is_verified' => false]);
        $this->actingAsUser($user);

        $this->postJson('/api/visits', ['place_id' => $unverified->id])
            ->assertNotFound()
            ->assertJsonPath('code', 'VISIT_PLACE_NOT_AVAILABLE');
    }

    public function test_soft_deleted_place_returns_404(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $place->delete();
        $this->actingAsUser($user);

        $this->postJson('/api/visits', ['place_id' => $place->id])
            ->assertNotFound()
            ->assertJsonPath('code', 'VISIT_PLACE_NOT_AVAILABLE');
    }

    public function test_history_requires_authentication(): void
    {
        $this->getJson('/api/visits')->assertUnauthorized();
    }

    public function test_history_returns_only_own_places_sorted_by_latest_visit(): void
    {
        Carbon::setTestNow('2026-08-19 08:00:00');

        $user = $this->createUserWithRole(RoleName::User);
        $other = $this->createUserWithRole(RoleName::User);

        $placeA = $this->createPlace(['name' => 'Place A']);
        $placeB = $this->createPlace(['name' => 'Place B']);
        $placeOther = $this->createPlace(['name' => 'Place Other']);

        VisitEvent::query()->create([
            'user_id' => $user->id,
            'place_id' => $placeA->id,
            'visit_date' => '2026-08-18',
            'visited_at' => '2026-08-18 02:00:00',
            'source' => 'detail',
        ]);
        Carbon::setTestNow('2026-08-19 08:00:00');
        VisitEvent::query()->create([
            'user_id' => $user->id,
            'place_id' => $placeB->id,
            'visit_date' => '2026-08-19',
            'visited_at' => '2026-08-19 01:00:00',
            'source' => 'discovery',
        ]);
        VisitEvent::query()->create([
            'user_id' => $other->id,
            'place_id' => $placeOther->id,
            'visit_date' => '2026-08-19',
            'visited_at' => '2026-08-19 01:30:00',
            'source' => 'detail',
        ]);

        $this->actingAsUser($user);

        $response = $this->getJson('/api/visits')->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', $placeB->id);
        $response->assertJsonPath('data.0.last_source', 'discovery');
        $response->assertJsonPath('data.1.id', $placeA->id);
        $response->assertJsonPath('meta.total', 2);
    }

    public function test_history_exposes_is_bookmarked_for_user(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();

        Bookmark::query()->create([
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);

        VisitEvent::query()->create([
            'user_id' => $user->id,
            'place_id' => $place->id,
            'visit_date' => '2026-08-19',
            'visited_at' => '2026-08-19 01:00:00',
            'source' => 'detail',
        ]);

        $this->actingAsUser($user);

        $this->getJson('/api/visits')
            ->assertOk()
            ->assertJsonPath('data.0.is_bookmarked', true);
    }

    public function test_history_hides_hidden_or_soft_deleted_place(): void
    {
        $user = $this->createUserWithRole(RoleName::User);

        $hidden = $this->createPlace(['status' => PlaceStatus::Hidden]);
        $deleted = $this->createPlace();
        $deleted->delete();
        $active = $this->createPlace(['name' => 'Active']);

        foreach ([$hidden, $deleted, $active] as $place) {
            VisitEvent::query()->create([
                'user_id' => $user->id,
                'place_id' => $place->id,
                'visit_date' => '2026-08-19',
                'visited_at' => '2026-08-19 01:00:00',
                'source' => 'detail',
            ]);
        }

        $this->actingAsUser($user);

        $response = $this->getJson('/api/visits')->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $active->id);
    }

    public function test_history_pagination(): void
    {
        Carbon::setTestNow('2026-08-19 08:00:00');

        $user = $this->createUserWithRole(RoleName::User);
        $this->actingAsUser($user);

        foreach (range(1, 3) as $i) {
            $place = $this->createPlace(['name' => "Place {$i}"]);
            VisitEvent::query()->create([
                'user_id' => $user->id,
                'place_id' => $place->id,
                'visit_date' => '2026-08-19',
                'visited_at' => "2026-08-19 0{$i}:00:00",
                'source' => 'detail',
            ]);
        }

        $response = $this->getJson('/api/visits?page=1&per_page=2')->assertOk();

        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_invalid_token_falls_back_to_guest(): void
    {
        // POST là public optional-auth (giống discovery): token sai không 401,
        // mà được coi là guest và bắt buộc phải có X-Anonymous-Id.
        $place = $this->createPlace();

        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/visits', ['place_id' => $place->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VISIT_ANONYMOUS_KEY_REQUIRED');
    }
}