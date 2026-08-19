<?php

use App\Http\Controllers\Api\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Api\Admin\Auth\AdminMeController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceDestroyController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceIndexController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceManagerIndexController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceManagerResendController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceManagerRevokeController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceManagerStoreController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceShowController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceStoreController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceUpdateController;
use App\Http\Controllers\Api\Admin\Place\AdminPlaceVerificationQueueController;
use App\Http\Controllers\Api\Admin\ManagerApplication\AdminManagerApplicationIndexController;
use App\Http\Controllers\Api\Admin\ManagerApplication\AdminManagerApplicationReviewController;
use App\Http\Controllers\Api\Admin\Tag\AdminTagStoreController;
use App\Http\Controllers\Api\Bookmark\BookmarkDestroyController;
use App\Http\Controllers\Api\Bookmark\BookmarkIndexController;
use App\Http\Controllers\Api\Bookmark\BookmarkStoreController;
use App\Http\Controllers\Api\Visit\VisitIndexController;
use App\Http\Controllers\Api\Visit\VisitStoreController;
use App\Http\Controllers\Api\ManagerApplication\SubmitManagerApplicationController;
use App\Http\Controllers\Api\Place\PlaceShowController;
use App\Http\Controllers\Api\Place\SearchPlaceController;
use App\Http\Controllers\Api\Auth\AccountSetupController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\GoogleAuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\UpdateProfileController;
use App\Http\Controllers\Api\Discovery\DiscoveryMetadataController;
use App\Http\Controllers\Api\Discovery\PlaceDiscoveryController;
use App\Http\Controllers\Api\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/test', TestController::class);

/*
 * Metadata công khai cho bộ lọc discovery.
 */
Route::get('/meta/discovery', DiscoveryMetadataController::class)
    ->middleware('throttle:60,1');

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
    // `/search` phải đứng trước `/{place}` để không bị route binding nuốt.
    Route::get('/search', SearchPlaceController::class)
        ->middleware('throttle:60,1');

    /*
     * Public detail endpoint. Public (guest/user), optional bearer token để
     * expose `is_bookmarked`. Place ẩn/chưa verified/soft-deleted trả 404.
     */
    Route::get('/{place}', PlaceShowController::class)
        ->whereNumber('place')
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

    Route::post('/account/setup', AccountSetupController::class)
        ->middleware('throttle:10,1');

    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1');
    // Google gọi bằng trình duyệt nên endpoint này trả redirect, không trả JSON.
    Route::get('/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1');
    Route::post('/google/exchange', [GoogleAuthController::class, 'exchange'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->middleware('role:user,sub_admin');
        Route::patch('/me', UpdateProfileController::class)->middleware('role:user,sub_admin');
        Route::post('/logout', LogoutController::class);
    });
});

/*
 * User thường xin làm Sub-admin cho place đã tồn tại. Chỉ user đã đăng nhập.
 */
Route::middleware(['auth:sanctum', 'role:user,sub_admin'])->group(function (): void {
    Route::post('/manager-applications', SubmitManagerApplicationController::class)
        ->middleware('throttle:10,1');
});

/*
 * Bookmark địa điểm yêu thích của User. Chỉ user đã đăng nhập, riêng tư.
 */
Route::middleware(['auth:sanctum', 'role:user,sub_admin'])->group(function (): void {
    Route::get('/bookmarks', BookmarkIndexController::class)
        ->middleware('throttle:60,1');
    Route::post('/bookmarks', BookmarkStoreController::class)
        ->middleware('throttle:30,1');
    Route::delete('/bookmarks/{place}', BookmarkDestroyController::class)
        ->middleware('throttle:30,1');
});

/*
 * Ghi nhận và xem lịch sử "Đi tới đó".
 *
 * POST là public với bearer token tùy chọn (resolve user qua guard sanctum như
 * discovery); GET yêu cầu bearer token user/sub_admin và chỉ trả lịch sử của
 * chính user đó.
 */
Route::post('/visits', VisitStoreController::class)
    ->middleware('throttle:30,1');

Route::middleware(['auth:sanctum', 'role:user,sub_admin'])->group(function (): void {
    Route::get('/visits', VisitIndexController::class)
        ->middleware('throttle:60,1');
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

        Route::post('/tags', AdminTagStoreController::class)->middleware('throttle:30,1');

        Route::get('/manager-applications', AdminManagerApplicationIndexController::class)->middleware('throttle:60,1');
        Route::post('/manager-applications/{id}/approve', [AdminManagerApplicationReviewController::class, 'approve'])->middleware('throttle:10,1');
        Route::post('/manager-applications/{id}/reject', [AdminManagerApplicationReviewController::class, 'reject'])->middleware('throttle:10,1');

        Route::prefix('places')->group(function (): void {
            Route::get('/', AdminPlaceIndexController::class)->middleware('throttle:60,1');
            Route::post('/', AdminPlaceStoreController::class)->middleware('throttle:30,1');
            Route::get('/verification-queue', AdminPlaceVerificationQueueController::class)->middleware('throttle:60,1');
            Route::get('/{place}', AdminPlaceShowController::class)->middleware('throttle:60,1');
            Route::patch('/{place}', AdminPlaceUpdateController::class)->middleware('throttle:30,1');
            Route::delete('/{place}', AdminPlaceDestroyController::class)->middleware('throttle:10,1');

            Route::get('/{place}/managers', AdminPlaceManagerIndexController::class)->middleware('throttle:60,1');
            Route::post('/{place}/managers', AdminPlaceManagerStoreController::class)->middleware('throttle:30,1');
            Route::post('/{place}/managers/{user}/resend', AdminPlaceManagerResendController::class)->middleware('throttle:10,1');
            Route::delete('/{place}/managers/{user}', AdminPlaceManagerRevokeController::class)->middleware('throttle:10,1');
        });
    });
});
