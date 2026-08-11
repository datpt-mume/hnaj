<?php

namespace App\Actions\Admin\Place;

use App\Models\Place;
use Illuminate\Support\Facades\DB;

class HardDeletePlace
{
    public function handle(Place $place): void
    {
        DB::transaction(function () use ($place): void {
            $placeId = $place->id;

            // Collect review/comment ids for nested image cleanup
            $reviewIds = DB::table('reviews')->where('place_id', $placeId)->pluck('id');
            $commentIds = DB::table('comments')->where('place_id', $placeId)->pluck('id');

            if ($reviewIds->isNotEmpty()) {
                DB::table('review_images')->whereIn('review_id', $reviewIds)->delete();
            }
            if ($commentIds->isNotEmpty()) {
                DB::table('comment_images')->whereIn('comment_id', $commentIds)->delete();
            }

            DB::table('reviews')->where('place_id', $placeId)->delete();

            // Delete leaf comments first to satisfy the self-referencing FK.
            while (true) {
                $leafIds = DB::table('comments as child')
                    ->where('child.place_id', $placeId)
                    ->whereNotExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('comments as descendant')
                            ->whereColumn('descendant.parent_id', 'child.id');
                    })
                    ->pluck('child.id');

                if ($leafIds->isEmpty()) {
                    break;
                }

                DB::table('comments')->whereIn('id', $leafIds)->delete();
            }

            DB::table('bookmarks')->where('place_id', $placeId)->delete();
            DB::table('visit_events')->where('place_id', $placeId)->delete();
            DB::table('anonymous_visit_events')->where('place_id', $placeId)->delete();
            DB::table('place_managers')->where('place_id', $placeId)->delete();
            DB::table('promotion_requests')->where('place_id', $placeId)->delete();
            DB::table('place_requests')->where('place_id', $placeId)->delete();
            DB::table('place_tags')->where('place_id', $placeId)->delete();
            DB::table('place_opening_hours')->where('place_id', $placeId)->delete();

            // Break thumbnail FK before deleting images
            DB::table('places')->where('id', $placeId)->update(['thumbnail_image_id' => null]);
            DB::table('place_images')->where('place_id', $placeId)->delete();

            // Moderation/notification deliveries are polymorphic; clean if referencing place
            DB::table('moderation_actions')->where('target_type', 'place')->where('target_id', $placeId)->delete();

            $place->forceDelete();
        });
    }
}
