<?php

namespace Tests\Feature\Place;

use App\Enums\PlaceStatus;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature test cho endpoint tìm kiếm địa điểm.
 *
 * Chạy trên MySQL (giống production) theo phpunit.xml, database riêng
 * `hnaj_test`. RefreshDatabase gọi migrate:fresh nên dữ liệu chỉ gồm những
 * gì setUp tạo ra.
 */
class PlaceSearchTest extends TestCase
{
    use RefreshDatabase;

    private District $district;

    private Category $categoryFood;

    private Category $categoryCafe;

    private Tag $tagStreetFood;

    private Tag $tagChill;

    protected function setUp(): void
    {
        parent::setUp();

        $this->district = District::factory()->create(['name' => 'Quận Test']);
        $this->categoryFood = Category::factory()->create(['name' => 'Ăn uống', 'slug' => 'an-uong']);
        $this->categoryCafe = Category::factory()->create(['name' => 'Cà phê & đồ uống', 'slug' => 'ca-phe-do-uong']);
        $this->tagStreetFood = Tag::factory()->create(['name' => 'Đồ ăn đường phố', 'slug' => 'do-an-duong-pho']);
        $this->tagChill = Tag::factory()->create(['name' => 'Chill', 'slug' => 'chill']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPlace(array $overrides = []): Place
    {
        return Place::factory()->create(array_merge([
            'district_id' => $this->district->id,
            'category_id' => $this->categoryFood->id,
            'status' => PlaceStatus::Active,
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function search(string $query, array $extra = []): array
    {
        $params = http_build_query(array_merge(['q' => $query], $extra));

        $response = $this->getJson('/api/places/search?'.$params);

        $response->assertOk();

        return $response->json();
    }

    public function test_search_matches_place_name_case_insensitive(): void
    {
        $place = $this->createPlace(['name' => 'Phở Gia Truyền']);

        $json = $this->search('PHỞ GIA');

        $this->assertSame([$place->id], array_column($json['data'], 'id'));
        $this->assertSame(1, $json['meta']['total']);
    }

    public function test_search_matches_address_text(): void
    {
        $place = $this->createPlace(['address_text' => '49 Bát Đàn, Hoàn Kiếm']);

        $json = $this->search('bát đàn');

        $this->assertSame([$place->id], array_column($json['data'], 'id'));
    }

    public function test_search_matches_tag_name(): void
    {
        $place = $this->createPlace(['name' => 'Quán ăn vỉa hè']);
        $place->tags()->attach($this->tagStreetFood->id);

        $json = $this->search('đường phố');

        $this->assertSame([$place->id], array_column($json['data'], 'id'));
    }

    public function test_search_matches_category_name(): void
    {
        $place = $this->createPlace(['category_id' => $this->categoryCafe->id, 'name' => 'Cà phê Nhà Thờ']);

        $json = $this->search('đồ uống');

        $this->assertSame([$place->id], array_column($json['data'], 'id'));
    }

    public function test_search_requires_all_tokens(): void
    {
        $matching = $this->createPlace(['name' => 'Phở Bò Bát Đàn']);
        $partial = $this->createPlace(['name' => 'Phở Gà Ngõ Trạm']);

        $json = $this->search('phở bò');

        $this->assertSame([$matching->id], array_column($json['data'], 'id'));
        $this->assertNotContains($partial->id, array_column($json['data'], 'id'));
    }

    public function test_search_excludes_hidden_and_soft_deleted_places(): void
    {
        $active = $this->createPlace(['name' => 'Quán Sạch']);
        $hidden = $this->createPlace(['name' => 'Quán Ẩn', 'status' => PlaceStatus::Hidden]);
        $deleted = $this->createPlace(['name' => 'Quán Xóa']);
        $deleted->delete();

        $json = $this->search('Quán');

        $this->assertSame([$active->id], array_column($json['data'], 'id'));
    }

    public function test_search_sorts_by_rating_desc_then_name_asc(): void
    {
        $low = $this->createPlace(['name' => 'Quán AAA', 'rating' => 3.5]);
        $high = $this->createPlace(['name' => 'Quán BBB', 'rating' => 4.8]);
        $midA = $this->createPlace(['name' => 'Quán Aaa', 'rating' => 4.0]);
        $midB = $this->createPlace(['name' => 'Quán Bbb', 'rating' => 4.0]);

        $json = $this->search('quán');

        $this->assertSame(
            [$high->id, $midA->id, $midB->id, $low->id],
            array_column($json['data'], 'id'),
        );
    }

    public function test_search_paginates_with_meta(): void
    {
        $places = collect(range(1, 12))
            ->map(fn (int $i) => $this->createPlace(['name' => 'Quán số '.$i, 'rating' => 4.0]));

        $json = $this->search('Quán', ['per_page' => 10]);

        $this->assertCount(10, $json['data']);
        $this->assertSame(12, $json['meta']['total']);
        $this->assertSame(1, $json['meta']['current_page']);
        $this->assertSame(2, $json['meta']['last_page']);
        $this->assertSame(10, $json['meta']['per_page']);

        $page2 = $this->search('Quán', ['per_page' => 10, 'page' => 2]);

        $this->assertCount(2, $page2['data']);
        $this->assertSame(2, $page2['meta']['current_page']);
        $this->assertNotContains($places->first()->id, array_column($page2['data'], 'id'));
    }

    public function test_search_page_beyond_range_returns_empty_list(): void
    {
        $this->createPlace(['name' => 'Quán Duy Nhất']);

        $json = $this->search('Quán', ['page' => 99]);

        $this->assertSame([], $json['data']);
        $this->assertSame(1, $json['meta']['total']);
    }

    public function test_search_no_match_returns_empty_list(): void
    {
        $json = $this->search('không tồn tại');

        $this->assertSame([], $json['data']);
        $this->assertSame(0, $json['meta']['total']);
    }

    public function test_search_escapes_like_wildcards(): void
    {
        $percent = $this->createPlace(['name' => 'Quán 100% Sạch']);
        $underscore = $this->createPlace(['name' => 'Quán A_B Cổ']);
        $this->createPlace(['name' => 'Quán 1000 Món']);
        $this->createPlace(['name' => 'Quán AB Cổ']);

        // "%" must match the literal percent sign, not every row.
        $json = $this->search('100%');

        $this->assertSame([$percent->id], array_column($json['data'], 'id'));

        // "_" must match the literal underscore, not any single character.
        $json = $this->search('A_B');

        $this->assertSame([$underscore->id], array_column($json['data'], 'id'));
    }

    public function test_search_requires_q(): void
    {
        $this->getJson('/api/places/search')
            ->assertStatus(422)
            ->assertJson(['success' => false, 'code' => 'VALIDATION_ERROR'])
            ->assertJsonValidationErrors('q');
    }

    public function test_search_rejects_blank_q(): void
    {
        $this->getJson('/api/places/search?q=%20%20')
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_search_rejects_overlong_q(): void
    {
        $this->getJson('/api/places/search?q='.str_repeat('a', 101))
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_search_rejects_overlong_per_page(): void
    {
        $this->getJson('/api/places/search?q=pho&per_page=51')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_search_response_item_shape_matches_card_contract(): void
    {
        $place = $this->createPlace(['name' => 'Quán Shape Test']);
        $place->tags()->attach($this->tagStreetFood->id);

        $json = $this->search('shape');

        $item = $json['data'][0];

        $this->assertSame($place->id, $item['id']);
        $this->assertSame('Quán Shape Test', $item['name']);
        $this->assertArrayHasKey('address_text', $item);
        $this->assertArrayHasKey('district', $item);
        $this->assertArrayHasKey('category', $item);
        $this->assertArrayHasKey('tags', $item);
        $this->assertArrayHasKey('min_price', $item);
        $this->assertArrayHasKey('max_price', $item);
        $this->assertArrayHasKey('rating', $item);
        $this->assertArrayHasKey('thumbnail', $item);
        $this->assertArrayHasKey('latitude', $item);
        $this->assertArrayHasKey('longitude', $item);
        $this->assertArrayHasKey('google_maps_url', $item);
        $this->assertArrayHasKey('opening_hours', $item);
    }
}
