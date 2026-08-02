<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\RevokeAccessTokens;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __invoke(Request $request, RevokeAccessTokens $revokeAccessTokens): JsonResponse
    {
        $revokeAccessTokens->handleCurrent($request->user());

        return ApiResponse::success(message: 'Signed out successfully.');
    }
}
