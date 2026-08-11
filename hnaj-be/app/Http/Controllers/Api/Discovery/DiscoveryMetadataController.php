<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Actions\Discovery\GetDiscoveryMetadata;
use App\Http\Controllers\Controller;
use App\Http\Resources\DiscoveryMetadataResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DiscoveryMetadataController extends Controller
{
    public function __invoke(GetDiscoveryMetadata $getDiscoveryMetadata): JsonResponse
    {
        return ApiResponse::success(
            data: new DiscoveryMetadataResource($getDiscoveryMetadata->handle()),
            message: 'Discovery metadata loaded successfully.',
        );
    }
}
