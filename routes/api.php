<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;

/*
|--------------------------------------------------------------------------
| Admin Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ColorController;
use App\Http\Controllers\Api\Admin\SizeController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Admin\DeliveryController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\AdminWarehouseController;
use App\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| User Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\User\CategoryController as UserCategoryController;
use App\Http\Controllers\Api\User\ProductController as UserProductController;
use App\Http\Controllers\Api\User\CartController;
use App\Http\Controllers\Api\User\OrderController;


use App\Http\Controllers\Api\User\WishlistController;
use App\Http\Controllers\Api\User\ReviewController;

/*
|--------------------------------------------------------------------------
| Delivery Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Delivery\DeliveryAuthController;
use App\Http\Controllers\Delivery\DeliveryStatusController;

/*
|--------------------------------------------------------------------------
| Notifications
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Chat
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\Chat\OrderChatController;
use App\Http\Controllers\ReportsController;

/*
|--------------------------------------------------------------------------
| Public Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:5,1')->group(function () {

    // Register & Login
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // OTP
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);

    // Logout
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

    // Password Reset
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
    Route::middleware('auth:sanctum')->post('/reset-password', [AuthController::class, 'resetPassword']);

    // Profile
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [UserProfileController::class, 'show']);
        Route::put('/profile/update', [UserProfileController::class, 'update']);

        
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Merged)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    /*
    | Categories
    */
    Route::get('/categories', [AdminCategoryController::class, 'index']);
    Route::get('/categories/archived', [AdminCategoryController::class, 'archived']);
    Route::post('/categories/{id}/restore', [AdminCategoryController::class, 'restore']);
    Route::get('/categories/{id}', [AdminCategoryController::class, 'show']);
    Route::post('/categories', [AdminCategoryController::class, 'store']);
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

    /*
    | Colors
    */
    Route::get('/colors', [ColorController::class, 'index']);
    Route::post('/colors', [ColorController::class, 'store']);
    Route::delete('/colors/{color}', [ColorController::class, 'destroy']);

    /*
    | Sizes
    */
    Route::get('/sizes', [SizeController::class, 'index']);
    Route::post('/sizes', [SizeController::class, 'store']);
    Route::delete('/sizes/{size}', [SizeController::class, 'destroy']);

    /*
    | Products
    */
    Route::get('/products', [AdminProductController::class, 'index']);
    Route::get('/products/{product}', [AdminProductController::class, 'show']);
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::post('/products/{product}/images', [AdminProductController::class, 'uploadImages']);
    Route::put('/products/{product}', [AdminProductController::class, 'update']);
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy']);
    Route::post('/products/{product}/restore', [AdminProductController::class, 'restore']);
    Route::post('/products/stock/add/{variantId}', [AdminProductController::class, 'addStock']);
    Route::put('/products/variants/{variantId}', [AdminProductController::class, 'updateVariant']);


    /*
    | Users
    */
    Route::get('/users/banned', [AdminUserController::class, 'banned']);
    Route::get('/users', [AdminUserController::class, 'index']);

    Route::prefix('users')->group(function () {
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::post('/{id}/ban', [AdminUserController::class, 'ban']);
        Route::post('/{id}/unban', [AdminUserController::class, 'unban']);
    });

    /*
    | Orders
    */
  Route::prefix('orders')->group(function () {
    Route::get('/', [AdminOrderController::class, 'index']);
 Route::get('/{id}', [AdminOrderController::class, 'show']);
Route::post('/{id}/status', [AdminOrderController::class, 'updateStatus']);
Route::get('/status/{status}', [AdminOrderController::class, 'getByStatus']);
});

    /*
    | Areas
    */
    Route::post('/create-area', [AreaController::class, 'store']);
    Route::delete('/areas/{id}', [AreaController::class, 'destroy']);
    Route::get('/areas', [AreaController::class, 'index']);

    /*
    | Deliveries
    */
    Route::get('/deliveries', [DeliveryController::class, 'index']);
    Route::get('/deliveries/{id}', [DeliveryController::class, 'show']);
    Route::put('/deliveries/{id}', [DeliveryController::class, 'update']);
    Route::delete('/deliveries/{id}', [DeliveryController::class, 'destroy']);
    Route::get('/deliveries/area/{areaId}', [DeliveryController::class, 'byArea']);

    /*
    |--------------------------------------------------------------------------
    | Warehouse Routes
    |--------------------------------------------------------------------------
    */

    // المستودعات CRUD
    Route::get('/warehouses', [AdminWarehouseController::class, 'index']);
    Route::get('/warehouses/{id}', [AdminWarehouseController::class, 'show']);
    Route::post('/warehouses', [AdminWarehouseController::class, 'store']);
    Route::put('/warehouses/{id}', [AdminWarehouseController::class, 'update']);
    Route::delete('/warehouses/{id}', [AdminWarehouseController::class, 'destroy']);

  
// ربط فاريانت بمستودع
    Route::post('/warehouses/{id}/attach-variant', [AdminWarehouseController::class, 'attachVariant']);

// إزالة فاريانت من مستودع
    Route::delete('/warehouses/{id}/detach-variant', [AdminWarehouseController::class, 'detachVariant']);

// عرض مخزون الفاريانت داخل مستودع
    Route::get('/warehouses/{id}/stock-variants', [AdminWarehouseController::class, 'stockVariants']);

    

    // المناطق المرتبطة بالمستودع
    Route::get('/warehouses/{id}/areas', [AdminWarehouseController::class, 'areas']);

    // ربط منطقة
    Route::post('/warehouses/{id}/attach-area', [AdminWarehouseController::class, 'attachArea']);

    // إزالة منطقة
    Route::post('/warehouses/{id}/detach-area', [AdminWarehouseController::class, 'detachArea']);

    // تنبيهات المخزون
    Route::get('/warehouses-alerts', [AdminWarehouseController::class, 'alerts']);
});



// ⚠️ محمي: لازم تسجيل دخول + صلاحية أدمن منشان حدا يشوف أرباح المتجر
Route::middleware(['auth:sanctum', 'admin'])->prefix('reports')->group(function () {

    // ---------------------------------------------------------
    // تقارير المستخدمين
    // ---------------------------------------------------------
    Route::get('/users', [ReportsController::class, 'usersStats']);

    // ---------------------------------------------------------
    // تقارير الطلبات
    // ---------------------------------------------------------
    Route::get('/orders', [ReportsController::class, 'ordersStats']);

    // ---------------------------------------------------------
    // تقارير الأرباح
    // ---------------------------------------------------------
    Route::get('/revenue', [ReportsController::class, 'revenueStats']);

    // ---------------------------------------------------------
    // حساب المتجر
    // ---------------------------------------------------------
    Route::get('/store-account', [ReportsController::class, 'storeAccount']);
    Route::get('/store-account/transactions', [ReportsController::class, 'storeTransactions']);

    // ---------------------------------------------------------
    // تقارير المستودعات
    // ---------------------------------------------------------
    Route::get('/warehouses', [ReportsController::class, 'warehouseStats']);

    // ---------------------------------------------------------
    // تقارير المنتجات
    // ---------------------------------------------------------
    Route::get('/products', [ReportsController::class, 'productsStats']);

    // ---------------------------------------------------------
    // تقارير النمو
    // ---------------------------------------------------------
    Route::get('/growth', [ReportsController::class, 'growthStats']);
});

Route::get('/notifications/admin', [NotificationController::class, 'adminNotifications'])
    ->middleware(['auth:sanctum', 'admin']);

/*
|--------------------------------------------------------------------------
| Delivery Routes
|--------------------------------------------------------------------------
*/

// إنشاء مندوب من قبل الأدمن
Route::middleware(['auth:sanctum', 'admin'])->post('/admin/create-delivery', [DeliveryController::class, 'store']);

// تسجيل دخول المندوب
Route::post('/delivery/login', [DeliveryAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'delivery'])->group(function () {

    // ⭐ عرض طلبات المندوب
    Route::get('/delivery/orders', [DeliveryStatusController::class, 'index']);

    // ⭐ تبديل حالة التوفر
    Route::post('/delivery/toggle-availability', [DeliveryStatusController::class, 'toggleAvailability']);

    // ⭐ قبول الطلب
    Route::post('/delivery/orders/{orderId}/accept', [DeliveryStatusController::class, 'acceptOrder']);


    // ⭐ رفض الطلب
    Route::post('/delivery/orders/{orderId}/reject', [DeliveryStatusController::class, 'rejectOrder']);

    // ⭐ المندوب في الطريق
    Route::post('/delivery/orders/{orderId}/on-the-way', [DeliveryStatusController::class, 'markOnTheWay']);

    // ⭐ التسليم بالباركود
    Route::post('/delivery/orders/{orderId}/deliver-with-barcode', [DeliveryStatusController::class, 'markDeliveredWithBarcode']);

    // ⭐ عرض تفاصيل طلب واحد
    Route::get('/delivery/orders/{orderId}', [DeliveryStatusController::class, 'show']);

    
    

});


Route::prefix('user')->group(function () {

    // ⭐ عرض كل المنتجات
    Route::get('/products', [UserProductController::class, 'index']);

    // ⭐ عرض تفاصيل منتج واحد
    Route::get('/products/{product}', [UserProductController::class, 'show']);

    // ⭐ عرض التصنيفات
    Route::get('/categories', [UserCategoryController::class, 'index']);

    // ⭐ فلترة المنتجات
    Route::get('/products/filter', [UserProductController::class, 'filter']);
});

Route::middleware(['auth:sanctum', 'user'])->prefix('user')->group(function () {
   //السلة

    Route::prefix('cart')->group(function () {

        Route::get('/', [CartController::class, 'index']);
        Route::post('/add-or-update', [CartController::class, 'addOrUpdate']);
        Route::put('/update-quantity/{cartItemId}', [CartController::class, 'updateQuantity']);
        Route::delete('/remove/{cartItemId}', [CartController::class, 'remove']);
        Route::delete('/clear', [CartController::class, 'clear']);
    });

    // ⭐ Orders Routes — الطلبات
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::get('/orders', [OrderController::class, 'myOrders']);
    Route::get('/orders/archived', [OrderController::class, 'archived']);
    Route::post('/orders/{id}/restore', [OrderController::class, 'restore']);
    Route::delete('/orders/{id}/force', [OrderController::class, 'forceDelete']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}', [OrderController::class, 'update']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);


  

    // ⭐ Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);

    // ⭐ Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::get('/reviews/{productId}', [ReviewController::class, 'index']);


});





/*
|--------------------------------------------------------------------------
| Chat Routes
|--------------------------------------------------------------------------
*/


Route::middleware(['auth:sanctum'])->group(function () {

    // إرسال رسالة
    Route::post('/orders/{orderId}/messages/send', [OrderChatController::class, 'send']);

    // جلب الرسائل
    Route::get('/orders/{orderId}/messages', [OrderChatController::class, 'messages']);

});



//المحفظة
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/wallet/add', [WalletController::class, 'addBalance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});