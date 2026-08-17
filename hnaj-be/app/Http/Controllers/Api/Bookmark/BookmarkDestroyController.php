<?php

namespace App\Http\Controllers\Api\Bookmark;

use App\Actions\Bookmark\DeleteBookmark;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkDestroyController extends Controller
{
    public function __invoke(Request $request, DeleteBookmark $deleteBookmark, int $place): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $deleteBookmark->handle((int) $user->id, $place);

        return ApiResponse::success(
            data: null,
            message: 'Đã bỏ lưu địa điểm.',
        );
    }
}
