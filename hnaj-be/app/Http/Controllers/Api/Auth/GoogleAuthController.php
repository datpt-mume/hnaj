<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\ExchangeGoogleCode;
use App\Actions\Auth\HandleGoogleCallback;
use App\Actions\Auth\RedirectToGoogle;
use App\Exceptions\AuthFlowException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleCallbackRequest;
use App\Http\Requests\Auth\GoogleExchangeRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class GoogleAuthController extends Controller
{
    /**
     * Trả URL đồng ý của Google để frontend tự điều hướng.
     */
    public function redirect(Request $request, RedirectToGoogle $redirectToGoogle): JsonResponse
    {
        $flowCookie = Str::random(64);

        return ApiResponse::success(
            data: ['authorization_url' => $redirectToGoogle->handle($flowCookie)],
            message: 'Google authorization URL created successfully.',
        )->withCookie($this->makeFlowCookie($request, $flowCookie));
    }

    /**
     * Google gọi lại endpoint này bằng trình duyệt nên phải redirect về frontend,
     * kèm exchange code dùng một lần thay vì bearer token.
     */
    public function callback(
        GoogleCallbackRequest $request,
        HandleGoogleCallback $handleGoogleCallback,
    ): RedirectResponse {
        $frontendUrl = (string) config('app.frontend_url');

        if ($request->hasProviderError()) {
            return $this->googleCallbackErrorRedirect($frontendUrl, 'GOOGLE_AUTH_FAILED');
        }

        try {
            $exchangeCode = $handleGoogleCallback->handle(
                $request->code(),
                $request->state(),
                $request->cookie(RedirectToGoogle::FLOW_COOKIE),
            );
        } catch (AuthFlowException $exception) {
            return $this->googleCallbackErrorRedirect($frontendUrl, $exception->errorCode->value);
        }

        return redirect()->away(
            $frontendUrl.'/auth/google/callback?code='.urlencode($exchangeCode)
        );
    }

    /**
     * Frontend đổi exchange code sang bearer token.
     */
    public function exchange(
        GoogleExchangeRequest $request,
        ExchangeGoogleCode $exchangeGoogleCode,
    ): JsonResponse {
        try {
            $result = $exchangeGoogleCode->handle(
                $request->code(),
                $request->cookie(RedirectToGoogle::FLOW_COOKIE),
            );
        } catch (AuthFlowException $exception) {
            return ApiResponse::error(
                message: $exception->getMessage(),
                code: $exception->errorCode->value,
                status: $exception->status,
            )->withoutCookie(RedirectToGoogle::FLOW_COOKIE, RedirectToGoogle::FLOW_COOKIE_PATH);
        }

        return ApiResponse::success(
            data: [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            message: 'Signed in with Google successfully.',
        )->withoutCookie(RedirectToGoogle::FLOW_COOKIE, RedirectToGoogle::FLOW_COOKIE_PATH);
    }

    private function googleCallbackErrorRedirect(string $frontendUrl, string $errorCode): RedirectResponse
    {
        return redirect()->away(
            $frontendUrl.'/auth/google/callback?error='.urlencode($errorCode)
        )->withoutCookie(RedirectToGoogle::FLOW_COOKIE, RedirectToGoogle::FLOW_COOKIE_PATH);
    }

    private function makeFlowCookie(Request $request, string $flowCookie): Cookie
    {
        return cookie(
            name: RedirectToGoogle::FLOW_COOKIE,
            value: $flowCookie,
            minutes: RedirectToGoogle::ttlMinutes(),
            path: RedirectToGoogle::FLOW_COOKIE_PATH,
            domain: null,
            secure: $request->isSecure() || (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        );
    }
}
