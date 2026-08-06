<?php

namespace Tests\Feature\Discovery;

use App\Enums\PlaceStatus;
use App\Enums\ScheduleType;
use App\Models\Category;
use App\Models\District;
use App\Models\Place;
use App\Models\Tag;
use App\Repositories\PlaceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature test cho endpoint khám phá/random địa điểm.
 *
 * Chạy trên MySQL (giống production) nhưng dùng database riêng `hnaj_test`
 * theo phpunit.xml. RefreshDatabase gọi `migrate:fresh` nên toàn bộ bảng bị
 * DROP mỗi lần chạy — các assertion dưới đây dựa vào việc database test chỉ
 * chứa dữ liệu do setUp tạo ra.
 */
class DiscoveryRandomTest extends TestCase
{
    use RefreshDatabase;

    private District $districtA;

    private District $districtB;

    private Category $categoryA;

    private Category $categoryB;

    private Tag $tagA;

    private Tag $tagB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->districtA = District::factory()->create(['name' => 'Quận A']);
        $this->districtB = District::factory()->create(['name' => 'Quận B']);
        $this->categoryA = Category::factory()->create(['slug' => 'danh-muc-a']);
        $this->categoryB = Category::factory()->create(['slug' => 'danh-muc-b']);
        $this->tagA = Tag::factory()->create(['slug' => 'tag-a']);
        $this->tagB = Tag::factory()->create(['slug' => 'tag-b']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPlace(array $overrides = []): Place
    {
        $place = Place::factory()->create(array_merge([
            'district_id' => $this->districtA->id,
            'category_id' => $this->categoryA->id,
            'status' => PlaceStatus::Active,
        ], $overrides));

        return $place;
    }

    /**
     * Gọi endpoint random và trả về dữ liệu data từ response.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function randomPlace(array $payload = []): ?array
    {
        $response = $this->postJson('/api/discovery/random', $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        return $response->json('data');
    }

    public function test_returns_an_active_place_when_no_filter(): void
    {
        $this->createPlace(['name' => 'Place Mở']);
        $this->createPlace(['name' => 'Place Khác']);

        $data = $this->randomPlace();

        $this->assertNotNull($data);
        $this->assertContains($data['name'], ['Place Mở', 'Place Khác']);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('district', $data);
        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('google_maps_url', $data);
    }

    public function test_filters_by_category(): void
    {
        $placeA = $this->createPlace(['name' => 'Ăn uống', 'category_id' => $this->categoryA->id]);
        $this->createPlace(['name' => 'Khác', 'category_id' => $this->categoryB->id]);

        $data = $this->randomPlace(['category_id' => $this->categoryA->id]);

        $this->assertNotNull($data);
        $this->assertSame($placeA->id, $data['id']);
    }

    public function test_filters_by_district(): void
    {
        $placeA = $this->createPlace(['name' => 'Quận A', 'district_id' => $this->districtA->id]);
        $this->createPlace(['name' => 'Quận B', 'district_id' => $this->districtB->id]);

        $data = $this->randomPlace(['district_id' => $this->districtA->id]);

        $this->assertNotNull($data);
        $this->assertSame($placeA->id, $data['id']);
    }

    public function test_filters_by_price_range_overlap(): void
    {
        $cheap = $this->createPlace(['name' => 'Rẻ', 'min_price' => 10000, 'max_price' => 50000]);
        $this->createPlace(['name' => 'Đắt', 'min_price' => 300000, 'max_price' => 500000]);

        $data = $this->randomPlace(['min_price' => 20000, 'max_price' => 100000]);

        $this->assertNotNull($data);
        $this->assertSame($cheap->id, $data['id']);
    }

    public function test_filters_by_tags_requires_all_selected(): void
    {
        $placeBoth = $this->createPlace(['name' => 'Cả hai tag']);
        $placeBoth->tags()->attach([$this->tagA->id, $this->tagB->id]);

        $placeOne = $this->createPlace(['name' => 'Chỉ tag A']);
        $placeOne->tags()->attach($this->tagA->id);

        $data = $this->randomPlace(['tag_ids' => [$this->tagA->id, $this->tagB->id]]);

        $this->assertNotNull($data);
        $this->assertSame($placeBoth->id, $data['id']);
    }

    public function test_filters_by_tags_any_single_tag(): void
    {
        $placeA = $this->createPlace(['name' => 'Tag A']);
        $placeA->tags()->attach($this->tagA->id);

        $this->createPlace(['name' => 'Không tag']);

        $data = $this->randomPlace(['tag_ids' => [$this->tagA->id]]);

        $this->assertNotNull($data);
        $this->assertSame($placeA->id, $data['id']);
    }

    public function test_hidden_place_is_never_returned(): void
    {
        $this->createPlace(['name' => 'Ẩn', 'status' => PlaceStatus::Hidden]);
        $this->createPlace(['name' => 'Hiện', 'status' => PlaceStatus::Active]);

        $data = $this->randomPlace();

        $this->assertNotNull($data);
        $this->assertSame('Hiện', $data['name']);
    }

    public function test_returns_null_when_no_place_matches_filters(): void
    {
        $this->createPlace(['name' => 'Chỉ A', 'category_id' => $this->categoryA->id]);

        $data = $this->randomPlace(['category_id' => $this->categoryB->id]);

        $this->assertNull($data);
    }

    public function test_excluded_place_ids_are_skipped(): void
    {
        $placeA = $this->createPlace(['name' => 'A']);
        $placeB = $this->createPlace(['name' => 'B']);

        $data = $this->randomPlace(['excluded_place_ids' => [$placeA->id]]);

        $this->assertNotNull($data);
        $this->assertSame($placeB->id, $data['id']);
    }

    public function test_excluded_all_matches_falls_back_to_random_without_excluded(): void
    {
        $only = $this->createPlace(['name' => 'Duy nhất']);

        $data = $this->randomPlace(['excluded_place_ids' => [$only->id]]);

        // Fallback (docs/prd.md §5.1): vẫn trả về place dù bị excluded vì không còn ứng viên khác.
        $this->assertNotNull($data);
        $this->assertSame($only->id, $data['id']);
    }

    public function test_filters_by_distance_within_radius(): void
    {
        $near = $this->createPlace(['name' => 'Gần', 'latitude' => 21.0285, 'longitude' => 105.8542]);
        $far = $this->createPlace(['name' => 'Xa', 'latitude' => 21.2000, 'longitude' => 105.8500]);

        $data = $this->randomPlace(['lat' => 21.0285, 'lng' => 105.8542, 'radius_km' => 5]);

        $this->assertNotNull($data);
        $this->assertSame($near->id, $data['id']);
    }

    public function test_distance_filter_and_district_are_applied_together(): void
    {
        $this->createPlace(['name' => 'Trong district A gần', 'district_id' => $this->districtA->id, 'latitude' => 21.0285, 'longitude' => 105.8542]);
        $this->createPlace(['name' => 'District B gần', 'district_id' => $this->districtB->id, 'latitude' => 21.0285, 'longitude' => 105.8542]);

        // Chọn district A + tọa độ gần => chỉ district A được xét.
        $data = $this->randomPlace(['district_id' => $this->districtA->id, 'lat' => 21.0285, 'lng' => 105.8542, 'radius_km' => 5]);

        $this->assertNotNull($data);
        $this->assertSame($this->districtA->id, $data['district']['id']);
    }

    public function test_open_now_keeps_place_without_opening_hours(): void
    {
        $this->createPlace(['name' => 'Không giờ']);

        $data = $this->randomPlace(['open_now' => true]);

        $this->assertNotNull($data);
        $this->assertSame('Không giờ', $data['name']);
    }

    public function test_open_now_filters_by_current_time(): void
    {
        // Thứ 5 lúc 10:00 giờ Việt Nam (day_of_week = 5 theo pipeline import).
        Carbon::setTestNow(Carbon::create(2026, 8, 6, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

        $openNow = $this->createPlace(['name' => 'Đang mở']);
        $openNow->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);

        $closed = $this->createPlace(['name' => 'Đã đóng']);
        $closed->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '06:00',
            'closes_at' => '09:00',
        ]);

        $data = $this->randomPlace(['open_now' => true]);

        $this->assertNotNull($data);
        $this->assertSame('Đang mở', $data['name']);

        Carbon::setTestNow();
    }

    public function test_open_now_excludes_closed_day(): void
    {
        // Thứ 5 lúc 10:00 giờ Việt Nam.
        Carbon::setTestNow(Carbon::create(2026, 8, 6, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

        $closed = $this->createPlace(['name' => 'Đóng hôm nay']);
        $closed->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Closed,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        $openAllDay = $this->createPlace(['name' => 'Mở cả ngày']);
        $openAllDay->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::AllDay,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        $data = $this->randomPlace(['open_now' => true]);

        $this->assertNotNull($data);
        $this->assertSame('Mở cả ngày', $data['name']);

        Carbon::setTestNow();
    }

    public function test_open_now_false_ignores_opening_hours(): void
    {
        // Thứ 5 lúc 23:00 giờ Việt Nam (ngoài khung 08-22).
        Carbon::setTestNow(Carbon::create(2026, 8, 6, 23, 0, 0, 'Asia/Ho_Chi_Minh'));

        $closedNow = $this->createPlace(['name' => 'Đóng theo giờ']);
        $closedNow->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);

        $data = $this->randomPlace(['open_now' => false]);

        $this->assertNotNull($data);
        $this->assertSame('Đóng theo giờ', $data['name']);

        Carbon::setTestNow();
    }

    public function test_validation_rejects_invalid_payload(): void
    {
        $this->postJson('/api/discovery/random', [
            'lat' => 21.0, // thiếu lng
            'radius_km' => 100, // ngoài khoảng
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_validation_rejects_too_many_excluded_place_ids(): void
    {
        $ids = Place::factory()
            ->count(PlaceRepository::MAX_EXCLUDED_IDS + 1)
            ->create()
            ->pluck('id')
            ->all();

        $this->postJson('/api/discovery/random', [
            'excluded_place_ids' => $ids,
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /**
     * Client serialize filter chưa chọn thành null tường minh. `validated()`
     * chỉ áp default khi key vắng mặt, nên null từng lọt xuống constructor và
     * gây TypeError (HTTP 500). Payload dưới đây phải trả 200.
     */
    public function test_explicit_null_filters_are_treated_as_absent(): void
    {
        $place = $this->createPlace(['name' => 'Null payload']);

        $data = $this->randomPlace([
            'category_id' => null,
            'district_id' => null,
            'min_price' => null,
            'max_price' => null,
            'tag_ids' => null,
            'open_now' => null,
            'lat' => null,
            'lng' => null,
            'radius_km' => null,
            'excluded_place_ids' => null,
        ]);

        $this->assertNotNull($data);
        $this->assertSame($place->id, $data['id']);
    }

    public function test_null_excluded_place_ids_does_not_error(): void
    {
        $place = $this->createPlace(['name' => 'Roll đầu tiên']);

        $data = $this->randomPlace(['excluded_place_ids' => null]);

        $this->assertNotNull($data);
        $this->assertSame($place->id, $data['id']);
    }

    /**
     * open_now = null phải hiểu là mặc định true (lọc giờ mở cửa), không phải
     * false và cũng không được ném TypeError.
     */
    public function test_null_open_now_falls_back_to_default_true(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 6, 10, 0, 0, 'Asia/Ho_Chi_Minh'));

        $open = $this->createPlace(['name' => 'Đang mở']);
        $open->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Regular,
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);

        $closed = $this->createPlace(['name' => 'Đã đóng']);
        $closed->openingHours()->create([
            'day_of_week' => 5,
            'schedule_type' => ScheduleType::Closed,
            'opens_at' => null,
            'closes_at' => null,
        ]);

        $data = $this->randomPlace(['open_now' => null]);

        $this->assertNotNull($data);
        $this->assertSame('Đang mở', $data['name']);

        Carbon::setTestNow();
    }

    /**
     * Trước đây limit(MAX_CANDIDATE_IDS) chạy TRƯỚC khi lọc khoảng cách nên
     * place nằm ngoài trang đầu theo id không bao giờ được chọn, và endpoint
     * báo "không tìm thấy" dù vẫn còn place khớp.
     */
    public function test_place_beyond_candidate_batch_is_still_reachable(): void
    {
        // Lấp đầy một lô ứng viên bằng place ở xa Hà Nội (id nhỏ hơn).
        Place::factory()
            ->count(PlaceRepository::MAX_CANDIDATE_IDS)
            ->create([
                'district_id' => $this->districtA->id,
                'category_id' => $this->categoryA->id,
                'status' => PlaceStatus::Active,
                'latitude' => 10.7769,
                'longitude' => 106.7009,
            ]);

        // Place duy nhất khớp bán kính, nằm sau toàn bộ lô trên.
        $near = $this->createPlace([
            'name' => 'Gần nhưng id lớn',
            'latitude' => 21.0285,
            'longitude' => 105.8542,
        ]);

        $data = $this->randomPlace([
            'lat' => 21.0285,
            'lng' => 105.8542,
            'radius_km' => 5,
            'open_now' => false,
        ]);

        $this->assertNotNull($data, 'Place khớp bộ lọc nhưng bị cắt khỏi tập ứng viên.');
        $this->assertSame($near->id, $data['id']);
    }
}
