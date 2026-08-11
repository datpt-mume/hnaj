<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\UpdateVerifiedPlace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Place\UpdateAdminPlaceRequest;
use App\Http\Resources\AdminPlaceResource;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use App\Repositories\AdminPlaceRepository;
use Illuminate\Http\JsonResponse;

class AdminPlaceUpdateController extends Controller
{
    public function __invoke(UpdateAdminPlaceRequest $request, int $place, UpdateVerifiedPlace $action, AdminPlaceRepository $repository): JsonResponse
    {
        $model = Place::query()->find($place);

        if ($model === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $updated = $action->handle($model, $request->validated());

        $nextId = $repository->nextUnverifiedId($updated->id);
        if ($nextId === null) {
            $nextId = $repository->nextUnverifiedId(null);
            if ($nextId === $updated->id) {
                $nextId = null;
            }
        }

        return ApiResponse::success(
            data: new AdminPlaceResource($updated),
            message: 'Đã cập nhật và xác minh địa điểm.',
            meta: ['next_unverified_id' => $nextId],
        );
    }
}
