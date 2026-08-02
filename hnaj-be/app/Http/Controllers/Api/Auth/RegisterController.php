<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->handle($request->registrationData());

        // Chưa phát hành token: tài khoản chỉ đăng nhập được sau khi xác thực email.
        return ApiResponse::success(
            data: ['user' => new UserResource($user)],
            message: 'Registration completed. Please check your email to verify your address.',
            status: 201,
        );
    }
}
