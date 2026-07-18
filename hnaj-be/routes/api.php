<?php

declare(strict_types=1);

use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/recommendations', RecommendationController::class);
});