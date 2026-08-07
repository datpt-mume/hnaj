<?php

namespace App\Http\Controllers\Api\Place;

use App\Actions\Place\SearchPlaces;
use App\Http\Controllers\Controller;
use App\Http\Requests\Place\SearchPlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class SearchPlaceController extends Controller
{
    public function __invoke(
        SearchPlaceRequest $request,
        SearchPlaces $searchPlaces,
    ): JsonResponse {
        $paginator = $searchPlaces->handle(
            $request->searchQuery(),
            $request->perPage(),
            (int) $request->validated('page', 1),
        );

        return ApiResponse::success(
            data: PlaceResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
