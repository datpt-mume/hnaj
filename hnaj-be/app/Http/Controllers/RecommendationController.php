<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RecommendationController extends Controller
{
    public function __construct(private readonly RecommendationService $service) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'location' => ['required', 'array'],
            'location.lat' => ['required', 'numeric', 'between:-90,90'],
            'location.lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['required', 'numeric', 'between:0.5,20'],
            'price_max' => ['required', 'integer', 'min:0'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:100'],
            'limit' => ['sometimes', 'integer', 'in:1,3'],
        ]);

        $places = $this->service->recommend($validated);
        $radius = (float) $validated['radius_km'];

        return response()->json([
            'success' => true,
            'data' => [
                'places' => $places,
                'meta' => [
                    'total_matched' => $places->count(),
                    'fallback_applied' => false,
                    'fallback_level' => 0,
                    'query_radius_km' => $radius,
                    'message_key' => $places->isEmpty() ? 'recommendation.no_results' : 'recommendation.results_found',
                    'relaxed_tags' => false,
                ],
            ],
        ]);
    }
}