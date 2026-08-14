<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\CreateAdminPlace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Place\CreateAdminPlaceRequest;
use App\Http\Resources\AdminPlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminPlaceStoreController extends Controller
{
    public function __invoke(CreateAdminPlaceRequest $request, CreateAdminPlace $action): JsonResponse
    {
        $place = $action->handle($request->user(), $request->validated());

        return ApiResponse::success(
            data: new AdminPlaceResource($place),
            message: 'Đã tạo địa điểm mới.',
            status: 201,
        );
    }
}
