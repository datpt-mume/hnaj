<?php

namespace Tests\Feature\Place;

use App\Enums\PlaceStatus;
use App\Enums\RoleName;
use App\Enums\ScheduleType;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\PlaceImage;
use App\Models\PlaceOpeningHour;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Feature test cho GET /api/places/{place} — chi tiết place công khai.
 */
class PlaceDetailTest extends AuthTestCase
{
    use RefreshDatabase;

    private District $district;

    private Category $category;

    private Tag $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->district = District::factory()->create(['name' => 'Hoàn Kiếm']);
        $this->category = Category::factory()->create([
            'name' => 'Ăn uống',
            'slug' => 'an-uong',
        ]);
        $this->tag = Tag::factory()->create([
            'name' => 'Đồ ăn đường phố',
            'slug' => 'do-an-duong-pho',
        ]);
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
            'description' => 'Phở bò truyền thống.',
            'phone' => '0123456789',
            'website_url' => 'https://example.com',
            'min_price' => 40000,
            'max_price' => 80000,
        ], $overrides));
    }

    private function attachVisibleImage(Place $place, string $url = 'https://cdn.example.com/a.jpg'): PlaceImage
    {
        return PlaceImage::query()->create([
            'place_id' => $place->id,
            'uploaded_by' => null,
            'image_url' => $url,
            'alt_text' => $place->name,
            'is_visible' => true,
        ]);
    }

    public function test_guest_can_view_public_place_detail(): void
    {
        $place = $this->createPlace(['name' => 'Phở Gia Truyền']);
        $place->tags()->attach($this->tag->id);
        $visible = $this->attachVisibleImage($place, 'https://cdn.example.com/visible.jpg');
        PlaceImage::query()->create([
            'place_id' => $place->id,
            'uploaded_by' => null,
            'image_url' => 'https://cdn.example.com/hidden.jpg',
            'alt_text' => 'hidden',
            'is_visible' => false,
        ]);
        PlaceOpeningHour::factory()->create([
            'place_id' => $place->id,
            'day_of_week' => 2,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '08:00',
            'closes_at' => '21:00',
        ]);
        $place->update(['thumbnail_image_id' => $visible->id]);

        $response = $this->getJson("/api/places/{$place->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $place->id)
            ->assertJsonPath('data.name', 'Phở Gia Truyền')
            ->assertJsonPath('data.description', 'Phở bò truyền thống.')
            ->assertJsonPath('data.phone', '0123456789')
            ->assertJsonPath('data.website_url', 'https://example.com')
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.district.name', 'Hoàn Kiếm')
            ->assertJsonPath('data.category.slug', 'an-uong')
            ->assertJsonPath('data.tags.0.slug', 'do-an-duong-pho')
            ->assertJsonPath('data.thumbnail.image_url', 'https://cdn.example.com/visible.jpg')
            ->assertJsonPath('data.images.0.image_url', 'https://cdn.example.com/visible.jpg')
            ->assertJsonPath('data.opening_hours.0.day_of_week', 2)
            ->assertJsonPath('data.opening_hours.0.schedule_type', 'regular')
            ->assertJsonPath('data.opening_hours.0.opens_at', '08:00')
            ->assertJsonPath('data.min_price', 40000)
            ->assertJsonPath('data.max_price', 80000);

        $this->assertCount(1, $response->json('data.images'));
        $this->assertArrayNotHasKey('is_bookmarked', $response->json('data'));
    }

    public function test_authenticated_user_receives_is_bookmarked_true_when_bookmarked(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();
        Bookmark::query()->create([
            'user_id' => $user->id,
            'place_id' => $place->id,
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/places/{$place->id}")
            ->assertOk()
            ->assertJsonPath('data.is_bookmarked', true);
    }

    public function test_authenticated_user_receives_is_bookmarked_false_when_not_bookmarked(): void
    {
        $user = $this->createUserWithRole(RoleName::User);
        $place = $this->createPlace();

        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/places/{$place->id}")
            ->assertOk()
            ->assertJsonPath('data.is_bookmarked', false);
    }

    public function test_hidden_place_returns_404(): void
    {
        $place = $this->createPlace(['status' => PlaceStatus::Hidden]);

        $this->getJson("/api/places/{$place->id}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_unverified_place_returns_404(): void
    {
        $place = $this->createPlace(['is_verified' => false]);

        $this->getJson("/api/places/{$place->id}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_soft_deleted_place_returns_404(): void
    {
        $place = $this->createPlace();
        $place->delete();

        $this->getJson("/api/places/{$place->id}")
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_missing_place_returns_404(): void
    {
        $this->getJson('/api/places/999999')
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    public function test_search_route_is_not_captured_by_place_detail(): void
    {
        // Regression: `/{place}` không được nuốt `/search`.
        $this->getJson('/api/places/search?q=pho')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
