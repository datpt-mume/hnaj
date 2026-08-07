<?php

namespace App\Http\Controllers\Api\Discovery;

use App\Actions\Discovery\SelectBestPlace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discovery\DiscoveryFilterRequest;
use App\Http\Resources\PlaceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlaceDiscoveryController extends Controller
{
    public function __invoke(DiscoveryFilterRequest $request, SelectBestPlace $selectBestPlace): JsonResponse
    {
        $place = $selectBestPlace->handle($request->filters());

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
