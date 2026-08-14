<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use App\Models\PlaceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlaceManagerIndexController extends Controller
{
    public function __invoke(Request $request, int $place): JsonResponse
    {
        $model = Place::query()->find($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $managers = PlaceManager::query()
            ->with(['user.roles'])
            ->where('place_id', $place)
            ->orderByDesc('assigned_at')
            ->get();

        return ApiResponse::success(
            data: $managers->map(fn (PlaceManager $manager) => [
                'id' => $manager->id,
                'place_id' => $manager->place_id,
                'user' => [
                    'id' => $manager->user->id,
                    'username' => $manager->user->username,
                    'full_name' => $manager->user->name,
                    'email' => $manager->user->email,
                    'email_verified' => $manager->user->hasVerifiedEmailAddress(),
                    'status' => $manager->user->status->value,
                ],
                'assigned_at' => $manager->assigned_at?->toIso8601String(),
                'revoked_at' => $manager->revoked_at?->toIso8601String(),
            ])->values(),
        );
    }
}
