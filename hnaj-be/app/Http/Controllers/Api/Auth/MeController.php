<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\LoadAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request, LoadAuthenticatedUser $loadAuthenticatedUser): JsonResponse
    {
        return ApiResponse::success(
            data: ['user' => new UserResource($loadAuthenticatedUser->handle($request->user()))],
            message: 'Authenticated user loaded successfully.',
        );
    }
}
