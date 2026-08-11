<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\HardDeletePlace;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use App\Repositories\AdminPlaceRepository;
use Illuminate\Http\JsonResponse;

class AdminPlaceDestroyController extends Controller
{
    public function __invoke(int $place, HardDeletePlace $action, AdminPlaceRepository $repository): JsonResponse
    {
        $model = Place::query()->find($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $nextId = $repository->nextUnverifiedId($model->id);

        $action->handle($model);

        if ($nextId === null) {
            $nextId = $repository->nextUnverifiedId(null);
        }

        return ApiResponse::success(
            data: null,
            message: 'Đã xóa địa điểm.',
            meta: ['next_unverified_id' => $nextId],
        );
    }
}
