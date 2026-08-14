<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfile $updateProfile): JsonResponse
    {
        $user = $updateProfile->handle($request->user(), $request->fullName());

        return ApiResponse::success(
            data: ['user' => new UserResource($user)],
            message: 'Profile updated successfully.',
        );
    }
}