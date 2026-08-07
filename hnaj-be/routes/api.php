<?php

use App\Http\Controllers\Api\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Api\Place\SearchPlaceController;
use App\Http\Controllers\Api\Admin\Auth\AdminMeController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Discovery\PlaceDiscoveryController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/test', TestController::class);

/*
 * Khám phá/random địa điểm.
 * Endpoint public (khách chưa đăng nhập dùng được), throttle chống spam.
 */
Route::prefix('discovery')->group(function (): void {
    Route::post('/random', PlaceDiscoveryController::class)
        ->middleware('throttle:30,1');
});

/*
 * Tìm kiếm địa điểm công khai. Public (khách chưa đăng nhập dùng được),
 * throttle chống spam query. Đặt trước mọi route `places/{place}` tương lai.
 */
Route::prefix('places')->group(function (): void {
    Route::get('/search', SearchPlaceController::class)
        ->middleware('throttle:60,1');
});

/*
 * Authentication cho người dùng thường.
 * Các endpoint public bị throttle vì nhận input chưa xác thực.
 */
Route::prefix('auth')->group(function (): void {
    Route::post('/register', RegisterController::class)->middleware('throttle:10,1');
    Route::post('/login', LoginController::class)->middleware('throttle:5,1');

    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:10,1');
    Route::post('/email/resend', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:5,1');

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1');
    // Google gọi bằng trình duyệt nên endpoint này trả redirect, không trả JSON.
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1');
    Route::post('/google/exchange', [GoogleAuthController::class, 'exchange'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->middleware('role:user');
        Route::post('/logout', LogoutController::class);
    });
});

/*
 * Khu vực quản trị. Đăng nhập tách riêng khỏi luồng người dùng thường và mọi
 * endpoint phía sau đều yêu cầu role admin được kiểm tra lại từ database.
 */
Route::prefix('admin')->group(function (): void {
    Route::post('/auth/login', AdminLoginController::class)->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
        Route::get('/auth/me', AdminMeController::class);
        Route::post('/auth/logout', LogoutController::class);
    });
});
