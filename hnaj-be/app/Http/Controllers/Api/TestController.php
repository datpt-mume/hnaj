<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

final class TestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success(
            data: [
                'service' => 'hnaj-be',
                'status' => 'ok',
            ],
            message: 'API connection is working.',
        );
    }
}
