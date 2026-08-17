<?php

namespace Tests\Feature\Bookmark;

use App\Enums\PlaceStatus;
use App\Enums\RoleName;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Feature test cho API bookmark địa điểm yêu thích.
 *
 * Chạy trên MySQL (giống production) dùng database riêng `hnaj_test` theo
 * phpunit.xml; RefreshDatabase gọi migrate:fresh mỗi lần chạy nên dữ liệu test
 * chỉ là những gì setUp tạo.
 */
class BookmarkTest extends AuthTestCase
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
        ], $overrides));
    }

    private function actingAsUser(User $user): void
    {
        Sanctum::actingAs($user, ['*']);
    }

    public function test_guest_cannot_list_bookmarks(): void
    {
        $this->getJson('/api/bookmarks')->assertUnauthorized();
    }

    public function test_guest_cannot_create_bookmark(): void
    {
        $place = $this->createPlace();

        $this->postJson('/api/bookmarks', ['place_id' => $place->id])
            ->assertUnauthorized();
    }

    public function test_user_can_create_bookmark(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $response = $this->postJson('/api/bookmarks', ['place_id' => $place->id]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.place_id', $place->id);
        $response->assertJsonPath('data.is_bookmarked', true);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);
    }

    public function test_duplicate_bookmark_returns_409(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $this->postJson('/api/bookmarks', ['place_id' => $place->id])->assertCreated();

        $this->postJson('/api/bookmarks', ['place_id' => $place->id])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'BOOKMARK_ALREADY_EXISTS');
    }

    public function test_place_must_be_active(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $hidden = $this->createPlace(['status' => PlaceStatus::Hidden]);
        $this->actingAsUser($user);

        $this->postJson('/api/bookmarks', ['place_id' => $hidden->id])
            ->assertNotFound()
            ->assertJsonPath('code', 'BOOKMARK_PLACE_NOT_AVAILABLE');
    }

    public function test_place_must_exist(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $this->actingAsUser($user);

        $this->postJson('/api/bookmarks', ['place_id' => 999999])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_user_can_delete_own_bookmark(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        Bookmark::query()->create([
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);
        $this->actingAsUser($user);

        $this->deleteJson("/api/bookmarks/{$place->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);
    }

    public function test_delete_missing_bookmark_returns_404(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        $this->actingAsUser($user);

        $this->deleteJson("/api/bookmarks/{$place->id}")
            ->assertNotFound()
            ->assertJsonPath('code', 'BOOKMARK_NOT_FOUND');
    }

    public function test_user_cannot_delete_others_bookmark(): void
    {
        $owner = $this->createUserWithRole(RoleName::User);
        $other = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        Bookmark::query()->create([
            'user_id' => $owner->id,
            'place_id' => $place->id,
        ]);
        $this->actingAsUser($other);

        $this->deleteJson("/api/bookmarks/{$place->id}")
            ->assertNotFound()
            ->assertJsonPath('code', 'BOOKMARK_NOT_FOUND');

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $owner->id,
            'place_id' => $place->id,
        ]);
    }

    public function test_list_only_returns_own_bookmarks(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $other = $this->createUserWithRole(RoleName::User);
        $mine = $this->createPlace(['name' => 'Của tôi']);
        $theirs = $this->createPlace(['name' => 'Của người khác']);
        Bookmark::query()->create([
            'user_id' => $user->id,
            'place_id' => $mine->id,
        ]);
        Bookmark::query()->create([
            'user_id' => $other->id,
            'place_id' => $theirs->id,
        ]);
        $this->actingAsUser($user);

        $response = $this->getJson('/api/bookmarks')->assertOk();

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $mine->id);
        $response->assertJsonPath('data.0.is_bookmarked', true);
        $response->assertJsonPath('data.0.name', 'Của tôi');
    }

    public function test_hidden_or_soft_deleted_place_is_hidden_from_list_but_record_kept(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $active = $this->createPlace(['name' => 'Đang mở']);
        $hidden = $this->createPlace(['name' => 'Bị ẩn', 'status' => PlaceStatus::Hidden]);
        $deleted = $this->createPlace(['name' => 'Đã xóa mềm']);
        $deleted->delete();

        foreach ([$active, $hidden, $deleted] as $place) {
            Bookmark::query()->create([
                'user_id' => $user->id,
                'place_id' => $place->id,
            ]);
        }
        $this->actingAsUser($user);

        $response = $this->getJson('/api/bookmarks')->assertOk();

        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $active->id);

        $this->assertDatabaseCount('bookmarks', 3);
    }

    public function test_list_is_paginated_and_sorted_by_newest(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $places = collect(range(1, 5))->map(
            fn (int $i): Place => $this->createPlace(['name' => "Place $i"]),
        );

        foreach ($places as $i => $place) {
            Bookmark::query()->create([
                'user_id' => $user->id,
                'place_id' => $place->id,
                'created_at' => Carbon::now()->addMinutes($i),
            ]);
        }
        $this->actingAsUser($user);

        $response = $this->getJson('/api/bookmarks?per_page=2&page=1')->assertOk();

        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.last_page', 3);
        $response->assertJsonCount(2, 'data');
        // Sắp xếp mới nhất trước: Place 5 rồi Place 4.
        $response->assertJsonPath('data.0.name', 'Place 5');
        $response->assertJsonPath('data.1.name', 'Place 4');
    }

    public function test_is_bookmarked_is_exposed_on_discovery_for_authenticated_user(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        Bookmark::query()->create([
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);
        $this->actingAsUser($user);

        $response = $this->postJson('/api/discovery/random', []);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $place->id);
        $response->assertJsonPath('data.is_bookmarked', true);
    }

    public function test_is_bookmarked_is_false_on_discovery_when_not_bookmarked(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $this->createPlace();
        $this->actingAsUser($user);

        $response = $this->postJson('/api/discovery/random', []);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.is_bookmarked', false);
    }

    public function test_is_bookmarked_is_absent_for_guest(): void
    {
        $this->createPlace();

        $response = $this->postJson('/api/discovery/random', []);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertArrayNotHasKey('is_bookmarked', $response->json('data'));
    }
}
