<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\PlaceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class AdminPlaceManagerRevokeController extends Controller
{
    public function __invoke(int $place, int $user): JsonResponse
    {
        $manager = PlaceManager::query()
            ->where('place_id', $place)
            ->where('user_id', $user)
            ->whereNull('revoked_at')
            ->first();

        if ($manager === null) {
            return ApiResponse::error('Không tìm thấy Sub-admin đang hoạt động của địa điểm này.', code: 'NOT_FOUND', status: 404);
        }

        $manager->revoked_at = Carbon::now();
        $manager->save();

        return ApiResponse::success(message: 'Đã thu hồi quyền quản lý địa điểm của Sub-admin.');
    }
}
