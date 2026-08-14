<?php

namespace App\Http\Controllers\Api\Admin\ManagerApplication;

use App\Actions\Admin\ManagerApplication\ReviewManagerApplication;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ManagerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminManagerApplicationReviewController extends Controller
{
    public function approve(Request $request, int $id, ReviewManagerApplication $action): JsonResponse
    {
        $application = ManagerApplication::query()->find($id);

        if ($application === null) {
            return ApiResponse::error('Không tìm thấy đơn xin quản lý.', code: 'NOT_FOUND', status: 404);
        }

        $application = $action->approve($application, $request->user());

        return ApiResponse::success(
            data: ['id' => $application->id, 'status' => $application->status->value],
            message: 'Đã duyệt và cấp quyền quản lý địa điểm.',
        );
    }

    public function reject(Request $request, int $id, ReviewManagerApplication $action): JsonResponse
    {
        $application = ManagerApplication::query()->find($id);

        if ($application === null) {
            return ApiResponse::error('Không tìm thấy đơn xin quản lý.', code: 'NOT_FOUND', status: 404);
        }

        $reason = trim((string) $request->input('reason', ''));

        if ($reason === '') {
            return ApiResponse::error('Vui lòng cung cấp lý do từ chối.', code: 'REASON_REQUIRED', status: 422);
        }

        $application = $action->reject($application, $request->user(), $reason);

        return ApiResponse::success(
            data: ['id' => $application->id, 'status' => $application->status->value],
            message: 'Đã từ chối đơn xin quản lý địa điểm.',
        );
    }
}
