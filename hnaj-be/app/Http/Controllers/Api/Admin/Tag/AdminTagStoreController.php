<?php

namespace App\Http\Controllers\Api\Admin\Tag;

use App\Actions\Admin\Tag\CreateTag;
use App\Http\Requests\Admin\Tag\CreateAdminTagRequest;
use App\Http\Resources\TagResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminTagStoreController
{
    public function __invoke(CreateAdminTagRequest $request, CreateTag $createTag): JsonResponse
    {
        $tag = $createTag->handle($request->validated('name'));

        return ApiResponse::success(
            data: new TagResource($tag),
            message: 'Tag created successfully.',
            status: 201,
        );
    }
}
