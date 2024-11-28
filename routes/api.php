<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileShopController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ShippingCategoryController;
use App\Http\Controllers\ShippingController;
use App\Http\Controllers\TestPaymentController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\USPSController;
use App\Http\Controllers\USPSPickupController;
use App\Http\Controllers\USPSPriceController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::get('/test', function () {
    return response()->json(['message' => 'API works!']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::delete('/discounts/{id}', [DiscountController::class, 'destroy']);
    Route::post('/products/{id}/status', [ProductController::class, 'updateStatus']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);


    Route::get('/user', [AuthController::class, 'getUser']);

    // Admin routes

    Route::prefix('admin')->group(function () {
        // Category routes
        Route::get('/categories/flat', [CategoryController::class, 'getFlatCategories']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/tree', [CategoryController::class, 'getTreeCategories']);
        Route::post('/categories/{id}', [CategoryController::class, 'update']);
        Route::get('/categories/{id}', [CategoryController::class, 'getCategoryById']);
        Route::post('/categories/{id}/status', [CategoryController::class, 'updateStatus']);
        Route::get('shipping/categories', [ShippingCategoryController::class, 'getRootCategories']);
        Route::post('/shipping-categories', [ShippingCategoryController::class, 'store']);

        Route::get('products', [ProductController::class, 'index']);
        // Product routes
        Route::get('/employees', [SellerController::class, 'getEmployees']);
        Route::post('/employees', [SellerController::class, 'store']);
        Route::post('/employees/{id}/status', [SellerController::class, 'updateStatus']);
        Route::delete('/employees/{id}', [SellerController::class, 'destroy']);
        Route::get('/employees/{id}', [SellerController::class, 'show']);
        Route::post('/employees/{id}', [SellerController::class, 'update']);

        Route::get('/customers', [CustomerController::class, 'getCustomers']);
        Route::post('/customers/{id}/status', [CustomerController::class, 'updateStatus']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);

        Route::post('products', [ProductController::class, 'store']);
        Route::get('products/{id}', [ProductController::class, 'show']);
        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::get('products/seller/{sellerId}', [ProductController::class, 'getProductsBySeller']);

        // Product color and size routes
        Route::get('products/{id}/colors', [ProductController::class, 'getColors']);
        Route::get('products/{id}/sizes', [ProductController::class, 'getSizes']);

        Route::prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('/', [DiscountController::class, 'store']);
            Route::get('/{id}', [DiscountController::class, 'show']);
            Route::post('/{id}/status', [DiscountController::class, 'updateStatus']);
        });


        // Get products by seller

    });
    Route::prefix('seller')->group(function () {
        Route::post('products/{id}/update', [ProductController::class, 'update']);
        Route::get('products/seller/{sellerId}', [ProductController::class, 'getProductsBySeller']);

        // Product color and size routes
        Route::get('products/{id}/colors', [ProductController::class, 'getColors']);
        Route::get('products/{id}/sizes', [ProductController::class, 'getSizes']);

        Route::prefix('discounts')->group(function () {
            Route::post('/{id}/update', [DiscountController::class, 'update']);
        });


        // Get products by seller

    });


    Route::post('/cart', [CartController::class, 'store']);
    Route::get('/cart/count', [CartController::class, 'countItems']);
    Route::get('/cart', [CartController::class, 'getCartItems']);
    Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity']);
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
    Route::get('/customer', [CustomerController::class, 'getCurrentCustomer']);
    Route::post('/profile', [CustomerController::class, 'updateCurrentUser']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::delete('/cart', [CartController::class, 'clearCart']);
    Route::get('/orders', [OrderController::class, 'getCustomerOrders']);
    Route::get('/shippings', [ShippingController::class, 'index']);
    Route::post('/shippings', [ShippingController::class, 'store']);
});
Route::get('/products/new', [ProductController::class, 'getNewProducts']);
Route::get('/products/best-selling', [ProductController::class, 'getBestSellingProducts']);

Route::get('/products/recently-viewed/{customerId}', [ProductController::class, 'getRecentlyViewedProducts']);
Route::get('/categories/parent', [CategoryController::class, 'getParentCategories']);
Route::get('/products/slug/{slug}', [ProductController::class, 'getProductDetailBySlug']);
Route::get('/products/related/{productId}', [ProductController::class, 'getRelatedProducts']);
Route::get('/products/same-seller/{productId}', [ProductController::class, 'getProductsBySameSeller']);
Route::get('products/seller/{sellerId}', [ProductController::class, 'getProductsBySeller']);
Route::get('discounts/seller/{sellerId}', [DiscountController::class, 'getDiscountsBySeller']);
Route::get('profile-shop/{sellerId}', [ProfileShopController::class, 'getShopInfo']);
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/latest', [PostController::class, 'getLatestPosts']);
Route::get('categories/all-parent', [CategoryController::class, 'getAllParentCategories']);
Route::get('topics', [TopicController::class, 'getAllTopics']);
Route::get('topics/{slug}', [TopicController::class, 'getTopicBySlug']);
Route::get('categories/{slug}/products', [CategoryController::class, 'getProductsByCategory']);
Route::get('categories/{slug}/child', [CategoryController::class, 'getChildCategories']);
Route::get('posts/topic/{slug}', [PostController::class, 'getPostsByTopic']);
Route::get('posts/related/{postId}', [PostController::class, 'getRelatedPosts']);
Route::get('post/slug/{slug}', [PostController::class, 'getPostBySlug']);
Route::get('posts/pages', [PostController::class, 'getPages']);
Route::get('post/page/{slug}', [PostController::class, 'getPageBySlug']);
Route::get('posts/other-pages/{slug}', [PostController::class, 'getOtherPages']);
Route::get('wishlist', [WishlistController::class, 'index']);
Route::get('products/shopprofile/{sellerId}', [ProfileShopController::class, 'getShopProducts']);
Route::get('products', [ProductController::class, 'getAllProducts']);
Route::get('products/search', [ProductController::class, 'search']);
Route::prefix('usps')->group(function () {
    Route::post('validate-address', [USPSController::class, 'validateAddress']);
    Route::post('pickup', [USPSPickupController::class, 'createPickup']);
    Route::post('prices', [USPSPriceController::class, 'getBaseRates']);
});
Route::prefix('payment-test')->group(function () {
    // Lấy thông tin gateway khả dụng
    Route::post('/get-gateway', [TestPaymentController::class, 'getGateway'])
        ->name('payment.test.get-gateway');

    // Lưu thông tin giao dịch
    Route::post('/save-transaction', [TestPaymentController::class, 'saveTransaction'])
        ->name('payment.test.save-transaction');

    // Xem trạng thái các gateway
    Route::get('/gateway-status', [TestPaymentController::class, 'getGatewayStatus'])
        ->name('payment.test.gateway-status');
    Route::post('/simulate-error', [TestPaymentController::class, 'simulateError'])
        ->name('payment.test.simulate-error');
    Route::post('/reset-daily-limits', [TestPaymentController::class, 'resetDailyLimits'])
        ->name('payment.test.reset-daily-limits');
});
