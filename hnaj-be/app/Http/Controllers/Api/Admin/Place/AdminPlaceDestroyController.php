<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\SoftDeletePlace;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use App\Repositories\AdminPlaceRepository;
use Illuminate\Http\JsonResponse;

class AdminPlaceDestroyController extends Controller
{
    public function __invoke(int $place, SoftDeletePlace $action, AdminPlaceRepository $repository): JsonResponse
    {
        $model = Place::query()->find($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $action->handle($model);

        return ApiResponse::success(
            data: null,
            message: 'Đã xóa địa điểm.',
        );
    }
}
