<?php

namespace App\Http\Controllers\Api\Admin\Place;

use App\Actions\Admin\Place\GetVerificationQueue;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminPlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlaceVerificationQueueController extends Controller
{
    public function __invoke(Request $request, GetVerificationQueue $action): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        $filters = [
            'q' => $request->query('q'),
            'district_id' => $request->query('district_id'),
            'category_id' => $request->query('category_id'),
            'tag_id' => $request->query('tag_id'),
        ];

        $paginator = $action->handle($filters, $perPage, $page);

        return ApiResponse::success(
            data: AdminPlaceResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_unverified' => $paginator->total(),
            ],
        );
    }
}
