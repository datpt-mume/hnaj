<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\ResendPlaceManagerSetup;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use App\Models\PlaceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlaceManagerResendController extends Controller
{
    public function __invoke(Request $request, int $place, int $user, ResendPlaceManagerSetup $action): JsonResponse
    {
        $manager = PlaceManager::query()
            ->where('place_id', $place)
            ->where('user_id', $user)
            ->whereNull('revoked_at')
            ->first();

        if ($manager === null) {
            return ApiResponse::error('Không tìm thấy Sub-admin của địa điểm này.', code: 'NOT_FOUND', status: 404);
        }

        $action->handle($manager);

        return ApiResponse::success(message: 'Đã gửi lại email kích hoạt.');
    }
}
