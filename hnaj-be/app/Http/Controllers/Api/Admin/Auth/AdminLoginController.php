<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Actions\Auth\LoginAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminLoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AdminLoginController extends Controller
{
    public function __invoke(AdminLoginRequest $request, LoginAdmin $loginAdmin): JsonResponse
    {
        $result = $loginAdmin->handle($request->username(), $request->password());

        return ApiResponse::success(
            data: [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: 'Signed in to the admin area successfully.',
        );
    }
}
