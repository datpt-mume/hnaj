<?php

namespace App\Http\Controllers\Api\Bookmark;

use App\Actions\Bookmark\CreateBookmark;
use App\Http\Controllers\Controller;
use App\Http\Requests\Bookmark\StoreBookmarkRequest;
use App\Http\Resources\BookmarkResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class BookmarkStoreController extends Controller
{
    public function __invoke(StoreBookmarkRequest $request, CreateBookmark $createBookmark): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $bookmark = $createBookmark->handle(
            (int) $user->id,
            (int) $request->validated('place_id'),
        );

        return ApiResponse::success(
            data: new BookmarkResource($bookmark),
            message: 'Đã lưu địa điểm yêu thích.',
            status: 201,
        );
    }
}
