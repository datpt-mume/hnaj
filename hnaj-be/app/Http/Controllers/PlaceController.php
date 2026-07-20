<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Place;
use App\Services\RecommendationService;
use App\Support\PlaceData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlaceController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'location' => ['sometimes', 'array'],
            'location.lat' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.lng' => ['required_with:location', 'numeric', 'between:-180,180'],
        ]);

        $place = Place::query()
            ->with(['category', 'district', 'ward', 'tags'])
            ->published()
            ->where('slug', $slug)
            ->first();

        if (! $place) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'place.not_found', 'message_key' => 'place.not_found'],
            ], 404);
        }

        $distanceKm = null;
        if (isset($validated['location'])) {
            $distanceKm = RecommendationService::distanceKm(
                (float) $validated['location']['lat'],
                (float) $validated['location']['lng'],
                $place->latitude,
                $place->longitude,
            );
        }

        return response()->json(['success' => true, 'data' => PlaceData::detail($place, $distanceKm)]);
    }
}
