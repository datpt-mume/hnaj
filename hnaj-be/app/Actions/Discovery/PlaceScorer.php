<?php

namespace App\Actions\Discovery;

use App\Models\Place;

/**
 * Scores discovery candidates.
 *
 * The discovery endpoint is no longer pure random: after hard filtering by the
 * user's filters, every remaining candidate gets a weighted composite score and
 * the highest-scoring place is chosen (random tie-break at the caller layer).
 *
 * Priority order, highest first, as decided:
 *   1. Not the place that just appeared (not in excluded)
 *   2. Place the user bookmarked
 *   3. Place the user pressed "Go there" for (visit event)
 *   4. Closer place
 *   5. Place with higher rating
 *
 * Weights are chosen so each criterion always beats the sum of all criteria
 * ranked below it (32 > 16+8+4 = 28, 16 > 8+4 = 12, 8 > 4). Priority order can
 * therefore never be flipped by accumulating small scores, while lower-ranked
 * criteria still decide when higher ones tie.
 */
class PlaceScorer
{
    /** Not in the current round's excluded_place_ids. */
    public const WEIGHT_NOT_EXCLUDED = 32.0;

    /** User bookmarked this place. */
    public const WEIGHT_BOOKMARKED = 16.0;

    /** User has a visit event ("Go there") for this place. */
    public const WEIGHT_VISITED = 8.0;

    /** Closer to the user's position scores higher; only when coordinates given. */
    public const WEIGHT_PROXIMITY = 4.0;

    /** The place's aggregate rating. */
    public const WEIGHT_RATING = 2.0;

    /** Maximum rating value, used to normalize to [0, 1]. */
    public const MAX_RATING = 5.0;

    /**
     * Score one candidate.
     *
     * @param  float|null  $distanceKm  distance computed at the filter layer;
     *                                  null when the user sent no coordinates.
     */
    public function score(Place $place, DiscoveryContext $context, ?float $distanceKm): float
    {
        $placeId = (int) $place->id;

        $score = 0.0;

        if (! isset($context->excludedIds[$placeId])) {
            $score += self::WEIGHT_NOT_EXCLUDED;
        }

        if (isset($context->bookmarkedIds[$placeId])) {
            $score += self::WEIGHT_BOOKMARKED;
        }

        if (isset($context->visitedIds[$placeId])) {
            $score += self::WEIGHT_VISITED;
        }

        $score += self::WEIGHT_PROXIMITY * $this->proximityFactor($distanceKm, $context->radiusKm);
        $score += self::WEIGHT_RATING * $this->ratingFactor($place->rating);

        return $score;
    }

    /**
     * Normalize proximity to [0, 1]: at the user's position = 1, exactly at the
     * radius boundary = 0. Without coordinates or radius this criterion is
     * neutral (0) for every candidate instead of randomly biased.
     */
    private function proximityFactor(?float $distanceKm, ?float $radiusKm): float
    {
        if ($distanceKm === null || $radiusKm === null || $radiusKm <= 0.0) {
            return 0.0;
        }

        return max(0.0, min(1.0, 1.0 - ($distanceKm / $radiusKm)));
    }

    /**
     * Normalize rating to [0, 1]. A place without rating data counts as maximum,
     * same "unknown is not penalized" principle as open_now.
     */
    private function ratingFactor(mixed $rating): float
    {
        if ($rating === null) {
            return 1.0;
        }

        return max(0.0, min(1.0, (float) $rating / self::MAX_RATING));
    }
}
