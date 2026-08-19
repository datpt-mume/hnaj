<?php

namespace App\Http\Controllers\Api\Visit;

use App\Actions\Visit\RecordVisit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Visit\StoreVisitRequest;
use App\Http\Resources\VisitResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/visits — ghi nhận một lượt "Đi tới đó".
 *
 * Public endpoint với bearer token tùy chọn: user/sub_admin ghi vào
 * `visit_events`; khách ghi vào `anonymous_visit_events` qua header
 * `X-Anonymous-Id`. Bấm lại cùng place trong cùng ngày là idempotent.
 */
class VisitStoreController extends Controller
{
    public function __invoke(StoreVisitRequest $request, RecordVisit $recordVisit): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user('sanctum');

        $anonymousId = $request->header('X-Anonymous-Id');

        $source = $request->validated('source') !== null
            ? (string) $request->validated('source')
            : 'detail';

        $recorded = $recordVisit->handle(
            (int) $request->validated('place_id'),
            $user,
            $anonymousId !== null ? (string) $anonymousId : null,
            $source,
        );

        return ApiResponse::success(
            data: new VisitResource($recorded),
            message: $recorded->created
                ? 'Đã ghi nhận lượt đi tới.'
                : 'Đã ghi nhận lượt đi tới trước đó.',
            status: $recorded->created ? 201 : 200,
        );
    }
}