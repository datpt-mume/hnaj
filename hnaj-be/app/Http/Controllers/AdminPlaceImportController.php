<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessPlaceImportBatch;
use App\Models\PlaceImportBatch;
use App\Services\PlaceImportPreviewService;
use App\Services\PlaceImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class AdminPlaceImportController extends Controller
{
    public function preview(Request $request, PlaceImportPreviewService $preview): JsonResponse
    {
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:512000']]);
        $file = $validated['file'];
        $batch = $preview->preview($file->getRealPath(), $file->getClientOriginalName());

        return response()->json(['success' => true, 'data' => $batch], 201);
    }

    public function confirm(PlaceImportBatch $batch, PlaceImportService $import): JsonResponse
    {
        try {
            $batch = $import->confirm($batch);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'import.invalid_transition',
                    'message_key' => 'import.invalid_transition',
                    'message' => $exception->getMessage(),
                ],
            ], 409);
        }
        ProcessPlaceImportBatch::dispatch($batch->id)->onQueue('imports');

        return response()->json(['success' => true, 'data' => $batch->load('rows')], 202);
    }

    public function pause(PlaceImportBatch $batch): JsonResponse
    {
        if (! in_array($batch->status, ['queued', 'processing'], true)) {
            return response()->json(['success' => false, 'error' => ['code' => 'import.invalid_transition', 'message_key' => 'import.invalid_transition']], 409);
        }

        $batch->update(['status' => 'pause_requested']);

        return response()->json(['success' => true, 'data' => $batch->fresh()->load('rows')]);
    }

    public function resume(PlaceImportBatch $batch): JsonResponse
    {
        if ($batch->status !== 'paused') {
            return response()->json(['success' => false, 'error' => ['code' => 'import.invalid_transition', 'message_key' => 'import.invalid_transition']], 409);
        }

        $batch->update(['status' => 'queued', 'paused_at' => null]);
        ProcessPlaceImportBatch::dispatch($batch->id)->onQueue('imports');

        return response()->json(['success' => true, 'data' => $batch->fresh()->load('rows')], 202);
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'in:uploaded,previewed,queued,processing,pause_requested,paused,completed,completed_with_errors'],
            'filename' => ['sometimes', 'string', 'max:255'],
        ]);

        $query = PlaceImportBatch::query()
            ->when(isset($validated['status']), fn ($query) => $query->where('status', $validated['status']))
            ->when(isset($validated['filename']), fn ($query) => $query->where('filename', 'like', '%'.$validated['filename'].'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'data' => $query->paginate($validated['per_page'] ?? 10, ['*'], 'page', $validated['page'] ?? 1),
        ]);
    }

    public function show(PlaceImportBatch $batch): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $batch->load('rows')]);
    }
}
