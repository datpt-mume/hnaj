<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\CreatePlaceManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Place\CreatePlaceManagerRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use Illuminate\Http\JsonResponse;

class AdminPlaceManagerStoreController extends Controller
{
    public function __invoke(CreatePlaceManagerRequest $request, int $place, CreatePlaceManager $action): JsonResponse
    {
        $model = Place::query()->find($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $user = $action->handle($model, $request->user(), $request->validated());

        return ApiResponse::success(
            data: new UserResource($user),
            message: 'Đã tạo tài khoản Sub-admin và gửi email kích hoạt.',
            status: 201,
        );
    }
}
