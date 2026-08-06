<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Actions\Discovery\GetRandomPlace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discovery\DiscoveryFilterRequest;
use App\Http\Resources\PlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlaceDiscoveryController extends Controller
{
    public function __invoke(DiscoveryFilterRequest $request, GetRandomPlace $getRandomPlace): JsonResponse
    {
        $place = $getRandomPlace->handle($request->filters());

        if ($place === null) {
            return ApiResponse::success(
                data: null,
                message: 'Không tìm thấy địa điểm phù hợp.',
            );
        }

        return ApiResponse::success(
            data: new PlaceResource($place),
            message: 'Request completed successfully.',
        );
    }
}
