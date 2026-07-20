<?php

declare(strict_types=1);

use App\Http\Controllers\AdminPlaceImportController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\CsrfCookieController;
use App\Http\Controllers\DiscoveryOptionsController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\PlaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/auth/csrf-cookie', CsrfCookieController::class)->middleware('web');
    Route::middleware('web')->group(function (): void {
        Route::post('/auth/register', [AuthController::class, 'register']);
        Route::post('/auth/login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('/auth/me', [AuthController::class, 'me']);
            Route::post('/auth/logout', [AuthController::class, 'logout']);
        });
    });
    Route::get('/categories', [DiscoveryOptionsController::class, 'categories']);
    Route::get('/districts', [DiscoveryOptionsController::class, 'districts']);
    Route::get('/tags', [DiscoveryOptionsController::class, 'tags']);
    Route::get('/recommendations', RecommendationController::class);
    Route::get('/places/{slug}', [PlaceController::class, 'show']);

    Route::middleware(['web', 'auth:sanctum', 'role:admin,editor'])->prefix('admin/imports/places')->group(function (): void {
        Route::post('/preview', [AdminPlaceImportController::class, 'preview']);
        Route::get('/', [AdminPlaceImportController::class, 'index']);
        Route::post('/{batch}/confirm', [AdminPlaceImportController::class, 'confirm']);
        Route::post('/{batch}/pause', [AdminPlaceImportController::class, 'pause']);
        Route::post('/{batch}/resume', [AdminPlaceImportController::class, 'resume']);
        Route::get('/{batch}', [AdminPlaceImportController::class, 'show']);
    });
});
