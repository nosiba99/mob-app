<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Api\AuthController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ColorController;
use App\Http\Controllers\Api\Admin\SizeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\AreaController;

// User Controllers
use App\Http\Controllers\Api\User\CategoryController as UserCategoryController;
use App\Http\Controllers\Api\User\ProductController as UserProductController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\OrderController;
use App\Http\Controllers\Api\User\WishlistController;
use App\Http\Controllers\Api\User\ReviewController;

// Delivery Controllers
use App\Http\Controllers\Delivery\DeliveryAuthController;
use App\Http\Controllers\Delivery\DeliveryOrderController;
use App\Http\Controllers\Delivery\DeliveryStatusController;




/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register',    [AuthController::class, 'register']);
    Route::post('/login',       [AuthController::class, 'login']);
    Route::post('/verify-otp',  [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp',  [AuthController::class, 'resendOtp']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);   
    Route::middleware('auth:sanctum')->post('/reset-password', [AuthController::class, 'resetPassword']);


});



/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::get('/categories/archived', [AdminCategoryController::class, 'archived']);
    Route::post('/categories/{id}/restore', [AdminCategoryController::class, 'restore']);
    Route::get('/categories/{id}', [AdminCategoryController::class, 'show']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

    // Colors
    

    Route::get('/colors', [ColorController::class, 'index']);
    Route::post('/colors', [ColorController::class, 'store']);
    Route::delete('/colors/{color}', [ColorController::class, 'destroy']);

    // Sizes
    Route::get('/sizes', [SizeController::class, 'index']);
    Route::post('/sizes', [SizeController::class, 'store']);
    Route::delete('/sizes/{size}', [SizeController::class, 'destroy']);

    // Products
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::get('/products/{product}', [AdminProductController::class, 'show']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::post('/products/{product}/images', [AdminProductController::class, 'uploadImages']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);
    Route::post('/products/{product}/restore', [AdminProductController::class, 'restore']);

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::post('/{id}/ban', [AdminUserController::class, 'ban']);
        Route::post('/{id}/unban', [AdminUserController::class, 'unban']);
    });

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [AdminOrderController::class, 'index']);
        Route::get('/{id}', [AdminOrderController::class, 'show']);
        Route::post('/{id}/status', [AdminOrderController::class, 'updateStatus']);
    });

    Route::post('/create-area', [AreaController::class, 'store']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);
    Route::get('/areas', [AreaController::class, 'index']);
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::put('/deliveries/{id}', [DeliveryController::class, 'update']);
    Route::delete('/deliveries/{id}', [DeliveryController::class, 'destroy']);
    Route::get('/deliveries/area/{areaId}', [DeliveryController::class, 'byArea']);


});



/*
|--------------------------------------------------------------------------
| Delivery Routes (Outside admin group)
|--------------------------------------------------------------------------
*/

// Admin creates delivery employee
Route::post('/admin/create-delivery', [DeliveryController::class, 'store']);

// Delivery login
Route::post('/delivery/login', [DeliveryAuthController::class, 'login']);

// Delivery orders
Route::middleware('auth:sanctum')->get('/delivery/orders', [DeliveryOrderController::class, 'index']);


Route::middleware('auth:sanctum')->post('/delivery/toggle-availability', [DeliveryStatusController::class, 'toggleAvailability']);




/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::prefix('user')->group(function () {
    Route::get('/products', [UserProductController::class, 'index']);
    Route::get('/products/{product}', [UserProductController::class, 'show']);
    Route::get('/categories', [UserCategoryController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {

    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'myOrders']);

    Route::get('/orders/archived', [OrderController::class, 'archived']);
    Route::post('/orders/{id}/restore', [OrderController::class, 'restore']);
    Route::delete('/orders/{id}/force', [OrderController::class, 'forceDelete']);

    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
});
