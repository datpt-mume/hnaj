<?php

namespace Tests\Unit;

use App\Actions\Discovery\DiscoveryContext;
use App\Actions\Discovery\PlaceScorer;
use App\Models\Place;
use PHPUnit\Framework\TestCase;

/**
 * Unit test cho thuật toán chấm điểm lượt khám phá (docs/prd.md §5.1).
 *
 * Không cần database: Place được dựng bằng setRawAttributes để chỉ giữ các
 * thuộc tính scorer thực sự đọc.
 */
class PlaceScorerTest extends TestCase
{
    private PlaceScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scorer = new PlaceScorer;
    }

    private function place(int $id, ?float $rating = 5.0): Place
    {
        $place = new Place;
        $place->setRawAttributes(['id' => $id, 'rating' => $rating], sync: true);

        return $place;
    }

    /**
     * Trọng số phải giảm dần đúng thứ tự ưu tiên đã chốt, và mỗi tiêu chí phải
     * lớn hơn tổng toàn bộ tiêu chí xếp sau để thứ tự không bị đảo do tích lũy.
     */
    public function test_weights_are_strictly_dominant_in_priority_order(): void
    {
        $this->assertGreaterThan(
            PlaceScorer::WEIGHT_BOOKMARKED + PlaceScorer::WEIGHT_VISITED
                + PlaceScorer::WEIGHT_PROXIMITY + PlaceScorer::WEIGHT_RATING,
            PlaceScorer::WEIGHT_NOT_EXCLUDED,
        );

        $this->assertGreaterThan(
            PlaceScorer::WEIGHT_VISITED + PlaceScorer::WEIGHT_PROXIMITY + PlaceScorer::WEIGHT_RATING,
            PlaceScorer::WEIGHT_BOOKMARKED,
        );

        $this->assertGreaterThan(
            PlaceScorer::WEIGHT_PROXIMITY + PlaceScorer::WEIGHT_RATING,
            PlaceScorer::WEIGHT_VISITED,
        );

        $this->assertGreaterThan(PlaceScorer::WEIGHT_RATING, PlaceScorer::WEIGHT_PROXIMITY);
    }

    public function test_excluded_place_scores_below_any_non_excluded_place(): void
    {
        $context = new DiscoveryContext(
            excludedIds: DiscoveryContext::toLookup([1]),
            bookmarkedIds: DiscoveryContext::toLookup([1]),
            visitedIds: DiscoveryContext::toLookup([1]),
        );

        // Place 1 thắng mọi tiêu chí phụ nhưng bị excluded; place 2 thì không.
        $excluded = $this->scorer->score($this->place(1, 5.0), $context, null);
        $fresh = $this->scorer->score($this->place(2, 0.0), $context, null);

        $this->assertGreaterThan($excluded, $fresh);
    }

    public function test_bookmark_outranks_visit_and_rating(): void
    {
        $context = new DiscoveryContext(
            bookmarkedIds: DiscoveryContext::toLookup([1]),
            visitedIds: DiscoveryContext::toLookup([2]),
        );

        $bookmarked = $this->scorer->score($this->place(1, 0.0), $context, null);
        $visited = $this->scorer->score($this->place(2, 5.0), $context, null);

        $this->assertGreaterThan($visited, $bookmarked);
    }

    public function test_proximity_outranks_rating(): void
    {
        $context = new DiscoveryContext(latitude: 21.0, longitude: 105.8, radiusKm: 5.0);

        $near = $this->scorer->score($this->place(1, 0.0), $context, 0.1);
        $far = $this->scorer->score($this->place(2, 5.0), $context, 4.9);

        $this->assertGreaterThan($far, $near);
    }

    public function test_higher_rating_wins_when_other_criteria_are_equal(): void
    {
        $context = new DiscoveryContext;

        $low = $this->scorer->score($this->place(1, 2.0), $context, null);
        $high = $this->scorer->score($this->place(2, 4.8), $context, null);

        $this->assertGreaterThan($low, $high);
    }

    public function test_null_rating_is_treated_as_maximum(): void
    {
        $context = new DiscoveryContext;

        $unknown = $this->scorer->score($this->place(1, null), $context, null);
        $max = $this->scorer->score($this->place(2, 5.0), $context, null);

        $this->assertSame($max, $unknown);
    }

    /**
     * Không có toạ độ => tiêu chí độ gần bằng 0 cho mọi ứng viên, kể cả khi
     * distance được truyền vào (không có bán kính để chuẩn hóa).
     */
    public function test_proximity_is_neutral_without_coordinates(): void
    {
        $context = new DiscoveryContext;

        $withDistance = $this->scorer->score($this->place(1, 5.0), $context, 0.1);
        $withoutDistance = $this->scorer->score($this->place(2, 5.0), $context, null);

        $this->assertSame($withoutDistance, $withDistance);
    }

    /**
     * Khoảng cách vượt bán kính (do sai số làm tròn ở tầng lọc) không được tạo
     * điểm âm làm hỏng thứ tự ưu tiên.
     */
    public function test_distance_beyond_radius_clamps_to_zero(): void
    {
        $context = new DiscoveryContext(latitude: 21.0, longitude: 105.8, radiusKm: 5.0);

        $score = $this->scorer->score($this->place(1, 0.0), $context, 12.0);

        $this->assertSame(PlaceScorer::WEIGHT_NOT_EXCLUDED, $score);
    }
}
