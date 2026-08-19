<?php

namespace App\Http\Controllers\Api\Visit;

use App\Actions\Visit\ListVisitHistory;
use App\Http\Controllers\Controller;
use App\Http\Resources\VisitHistoryResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/visits — lịch sử "Đi tới đó" của User, unique theo place.
 */
class VisitIndexController extends Controller
{
    public function __invoke(Request $request, ListVisitHistory $listVisitHistory): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        /** @var \App\Models\User $user */
        $user = $request->user();

        $paginator = $listVisitHistory->handle((int) $user->id, $perPage, $page);

        return ApiResponse::success(
            data: VisitHistoryResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}