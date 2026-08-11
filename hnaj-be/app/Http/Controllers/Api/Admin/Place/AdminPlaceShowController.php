<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminPlaceResource;
use App\Http\Responses\ApiResponse;
use App\Repositories\AdminPlaceRepository;
use Illuminate\Http\JsonResponse;

class AdminPlaceShowController extends Controller
{
    public function __invoke(int $place, AdminPlaceRepository $repository): JsonResponse
    {
        $model = $repository->findForAdmin($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        return ApiResponse::success(data: new AdminPlaceResource($model));
    }
}
