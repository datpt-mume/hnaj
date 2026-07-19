<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PlaceImportBatch;
use App\Services\PlaceImportPreviewService;
use App\Services\PlaceImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminPlaceImportController extends Controller
{
    public function preview(Request $request, PlaceImportPreviewService $preview): JsonResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:512000']]);
        $file = $validated['file'];
        $batch = $preview->preview($file->getRealPath(), $file->getClientOriginalName());

        return response()->json(['data' => $batch], 201);
    }

    public function confirm(PlaceImportBatch $batch, PlaceImportService $import): JsonResponse
    {
        $batch = $import->confirm($batch);

        return response()->json(['data' => $batch], $batch->status === 'completed' ? 200 : 202);
    }

    public function show(PlaceImportBatch $batch): JsonResponse
    {
        return response()->json(['data' => $batch->load('rows')]);
    }
}