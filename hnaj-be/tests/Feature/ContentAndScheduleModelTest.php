<?php

namespace Tests\Feature;

use App\Models\AnonymousVisitEvent;
use App\Models\Comment;
use App\Models\CommentImage;
use App\Models\PlaceOpeningHour;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContentAndScheduleModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_visit_event_uses_managed_timestamps(): void
    {
        $event = new AnonymousVisitEvent();

        $this->assertTrue($event->usesTimestamps());
        $this->assertContains('created_at', $event->getDates());
        $this->assertContains('updated_at', $event->getDates());
    }

    public function test_review_and_comment_image_tables_have_expected_schema(): void
    {
        foreach (['review_images', 'comment_images'] as $table) {
            $this->assertTrue(Schema::hasColumns($table, [
                'id',
                str_replace('_images', '_id', $table),
                'image_url',
                'alt_text',
                'sort_order',
                'created_at',
                'updated_at',
                'deleted_at',
            ]));
        }
    }

    public function test_opening_hours_no_longer_support_crossing_midnight(): void
    {
        $this->assertFalse(Schema::hasColumn('place_opening_hours', 'crosses_midnight'));
        $this->assertFalse(in_array('crosses_midnight', (new PlaceOpeningHour())->getFillable(), true));
    }

    public function test_opening_hour_values_are_normalized_to_hh_mm(): void
    {
        $openingHour = new PlaceOpeningHour([
            'opens_at' => '08:00',
            'closes_at' => '22:00',
        ]);

        $this->assertSame('08:00:00', $openingHour->getAttributes()['opens_at']);
        $this->assertSame('22:00:00', $openingHour->getAttributes()['closes_at']);
        $this->assertSame('08:00', $openingHour->opens_at);
        $this->assertSame('22:00', $openingHour->closes_at);
    }

    public function test_review_has_one_per_user_place_constraint_and_images_without_comments(): void
    {
        $review = new Review();

        $this->assertTrue(Schema::hasColumn('reviews', 'body'));
        $this->assertTrue(Schema::hasColumn('reviews', 'rating'));
        $this->assertTrue($review->images()->getRelated() instanceof ReviewImage);
        $this->assertFalse(method_exists($review, 'comments'));

        $indexes = Schema::getIndexes('reviews');
        $this->assertTrue(collect($indexes)->contains(
            fn (array $index): bool => $index['unique']
                && $index['columns'] === ['user_id', 'place_id']
        ));
    }

    public function test_comment_supports_nested_replies_and_images(): void
    {
        $comment = new Comment();

        $this->assertTrue($comment->replies()->getRelated() instanceof Comment);
        $this->assertTrue($comment->images()->getRelated() instanceof CommentImage);
        $this->assertSame('parent_id', $comment->replies()->getForeignKeyName());
    }
}
