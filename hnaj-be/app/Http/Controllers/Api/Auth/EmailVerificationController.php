<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\ResendEmailVerification;
use App\Actions\Auth\VerifyEmail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function verify(VerifyEmailRequest $request, VerifyEmail $verifyEmail): JsonResponse
    {
        $user = $verifyEmail->handle($request->token());

        return ApiResponse::success(
            data: ['user' => new UserResource($user)],
            message: 'Email verified successfully. You can sign in now.',
        );
    }

    public function resend(ResendVerificationRequest $request, ResendEmailVerification $resend): JsonResponse
    {
        $resend->handle($request->email());

        // Thông điệp trung lập để không tiết lộ email nào đã đăng ký.
        return ApiResponse::success(
            message: 'If the email address needs verification, a new link has been sent.',
        );
    }
}
