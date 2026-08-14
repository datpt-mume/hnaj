<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\CompleteAccountSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AccountSetupRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class AccountSetupController extends Controller
{
    public function __invoke(AccountSetupRequest $request, CompleteAccountSetup $complete): JsonResponse
    {
        $user = $complete->handle($request->validated('token'), $request->validated('password'));

        return ApiResponse::success(
            data: ['user' => new UserResource($user)],
            message: 'Tài khoản đã được kích hoạt. Bạn có thể đăng nhập.',
        );
    }
}
