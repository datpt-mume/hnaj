<?php

namespace App\Http\Controllers\Api\Admin\ManagerApplication;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ManagerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminManagerApplicationIndexController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = ManagerApplication::query()
            ->with(['place.district', 'applicant.roles'])
            ->orderByDesc('created_at');

        if (! empty($status)) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(
            max(1, min(50, (int) $request->query('per_page', 10))),
            ['*'],
            'page',
            max(1, (int) $request->query('page', 1)),
        );

        return ApiResponse::success(
            data: $applications->items(),
            meta: [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        );
    }
}
