<?php

namespace App\Http\Controllers\Api\Bookmark;

use App\Actions\Bookmark\ListBookmarks;
use App\Http\Controllers\Controller;
use App\Http\Resources\PlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkIndexController extends Controller
{
    public function __invoke(Request $request, ListBookmarks $listBookmarks): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));

        /** @var \App\Models\User $user */
        $user = $request->user();

        $paginator = $listBookmarks->handle((int) $user->id, $perPage, $page);

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
