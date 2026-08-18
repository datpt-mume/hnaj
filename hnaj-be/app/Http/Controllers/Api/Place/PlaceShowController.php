<?php

namespace App\Http\Controllers\Api\Place;

use App\Actions\Place\ShowPlace;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceDetailResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Chi tiết place công khai cho trang `/places/{place}`.
 *
 * Public endpoint: guest và user đã đăng nhập đều truy cập được. Khi request
 * có bearer token hợp lệ, response trả thêm `is_bookmarked`. Place ẩn,
 * chưa xác minh hoặc đã soft-delete đều trả 404 (không 403) để không lộ sự
 * tồn tại của place bị ẩn.
 */
class PlaceShowController extends Controller
{
    public function __invoke(Request $request, int $place, ShowPlace $showPlace): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user('sanctum');

        $model = $showPlace->handle($place, $user !== null ? (int) $user->id : null);

        if ($model === null) {
            return ApiResponse::error(
                'Không tìm thấy địa điểm hoặc địa điểm không công khai.',
                code: 'NOT_FOUND',
                status: 404,
            );
        }

        return ApiResponse::success(data: new PlaceDetailResource($model));
    }
}