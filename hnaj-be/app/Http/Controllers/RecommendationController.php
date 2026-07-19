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
            'district_id' => ['sometimes', 'string', 'exists:administrative_areas,id'],
            'category_slug' => ['sometimes', 'string', 'exists:categories,slug'],
            'price_min' => ['sometimes', 'integer', 'min:0'],
            'price_max' => ['sometimes', 'nullable', 'integer', 'min:0', 'gte:price_min'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'limit' => ['sometimes', 'integer', 'in:1,3'],
        ]);

        $recommendation = $this->service->recommend($validated);

        return response()->json([
            'success' => true,
            'data' => $recommendation,
        ]);
    }
}
