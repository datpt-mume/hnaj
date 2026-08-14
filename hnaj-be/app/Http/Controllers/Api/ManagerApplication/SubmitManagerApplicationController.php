<?php

namespace App\Http\Controllers\Api\ManagerApplication;

use App\Actions\ManagerApplication\SubmitManagerApplication;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManagerApplication\SubmitManagerApplicationRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Place;
use Illuminate\Http\JsonResponse;

class SubmitManagerApplicationController extends Controller
{
    public function __invoke(SubmitManagerApplicationRequest $request, SubmitManagerApplication $action): JsonResponse
    {
        $place = Place::query()->find($request->validated('place_id'));

        if ($place === null) {
            return ApiResponse::error('Không tìm thấy địa điểm.', code: 'NOT_FOUND', status: 404);
        }

        $application = $action->handle($request->user(), $place, $request->validated());

        return ApiResponse::success(
            data: ['id' => $application->id, 'status' => $application->status->value],
            message: 'Đã gửi đơn xin quản lý địa điểm. Vui lòng chờ Admin duyệt.',
            status: 201,
        );
    }
}
