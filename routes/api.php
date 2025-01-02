<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductTemplateController;
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

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);


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
    Route::get('/check-session', [AuthController::class, 'checkSession']);

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



        Route::delete('products/{id}', [ProductController::class, 'destroy']);
        Route::get('products/seller/{sellerId}', [ProductController::class, 'getProductsBySeller']);

        // Product color and size routes
        Route::get('products/{id}/colors', [ProductController::class, 'getColors']);
        Route::get('products/{id}/sizes', [ProductController::class, 'getSizes']);
        Route::post('pages', [PostController::class, 'createStaticPage']);
        Route::post('/post/{id}/status', [PostController::class, 'updateStatus']);
        Route::delete('/post/{id}', [PostController::class, 'destroy']);
        Route::post('/page/{id}', [PostController::class, 'updateStaticPage']);
        Route::get('/post/{id}', [PostController::class, 'getPageById']);



        Route::get('/orders', [OrderController::class, 'getAllOrders']);




        Route::prefix('discounts')->group(function () {
            Route::get('/', [DiscountController::class, 'index']);
            Route::post('/', [DiscountController::class, 'store']);
            Route::get('/{id}', [DiscountController::class, 'show']);
            Route::post('/{id}/status', [DiscountController::class, 'updateStatus']);
        });
        Route::get('/orders', [OrderController::class, 'getAllOrders']);
        Route::post('pages', [PostController::class, 'createStaticPage']);
        Route::post('/post/{id}/status', [PostController::class, 'updateStatus']);
        Route::delete('/post/{id}', [PostController::class, 'destroy']);
        Route::post('/page/{id}', [PostController::class, 'updateStaticPage']);
        Route::get('/post/{id}', [PostController::class, 'getPageById']);
        Route::get('posts', [PostController::class, 'index']);
        Route::post('/profileshop', [ProfileShopController::class, 'create']);
        Route::get('/sellers-without-profile-shop', [ProfileShopController::class, 'getSellersWithoutProfileShop']);

        Route::post('/send-email', [AdminController::class, 'sendCustomEmail'])->name('admin.sendCustomEmail');
        Route::get('/search', [ProductController::class, 'searchAllProducts']);
        Route::post('/addTopic', [TopicController::class, 'addTopic']);
        Route::delete('/topics/{id}', [TopicController::class, 'deleteTopic']);



        // Get products by seller

    });
    Route::prefix('seller')->group(function () {
        Route::post('products', [ProductController::class, 'store']);
        Route::get('products/seller', [ProductController::class, 'getProductsBySeller']);
        Route::post('products/{id}/update', [ProductController::class, 'update']);

        Route::post('profile', [ProfileShopController::class, 'createOrUpdateProfile']);
        Route::get('orders', [OrderController::class, 'getOrdersBySeller']);
        // Product color and size routes
        Route::get('products/{id}', [ProductController::class, 'show']);

        Route::prefix('discounts')->group(function () {
            Route::post('/{id}/update', [DiscountController::class, 'update']);
            Route::get('seller/{sellerId}', [DiscountController::class, 'getDiscountsBySeller']);
        });
        Route::get('profile-shop/{sellerId}', [ProfileShopController::class, 'getShopInfo']);
        Route::get('discounts/seller/{sellerId}', [DiscountController::class, 'getDiscountsBySeller']);
        Route::get('posts', [PostController::class, 'getPostsByAuth']);
        Route::post('post', [PostController::class, 'createPost']);
        Route::post('post/{id}/update', [PostController::class, 'updatePost']);
        Route::get('products/types/{productId}', [ProductController::class, 'getTypes']);



        Route::get('/orders/{orderId}', [OrderController::class, 'getOrderDetail']);
        Route::get('posts', [PostController::class, 'getPostsByAuth']);
        Route::post('post', [PostController::class, 'createPost']);
        Route::post('post/{id}/update', [PostController::class, 'updatePost']);
        // Get products by seller
        Route::post('/shippings/{id}/update-tracking', [ShippingController::class, 'updateTracking']);
        Route::post('/orders/{orderId}/status', [OrderController::class, 'updateStatus']);
        Route::get('/product/search', [ProductController::class, 'searchBySeller']);
        Route::post('/products/{id}/copy', [ProductController::class, 'copyProduct']);
        Route::get('check-profile', [ProfileShopController::class, 'checkProfileExists']);
        Route::get('profile', [ProfileShopController::class, 'getProfileShopInfo']);

        Route::post('/product-templates', [ProductTemplateController::class, 'store']);
        Route::get('/product-templates', [ProductTemplateController::class, 'index']);
        Route::post('/products/import', [ProductController::class, 'importProducts']);
        Route::post('/product-templates/{id}', [ProductTemplateController::class, 'update']);
        Route::delete('/product-templates/{id}', [ProductTemplateController::class, 'destroy']);
        Route::get('/templates/{id}', [ProductTemplateController::class, 'show']);
        Route::post('/products/add-by-template', [ProductController::class, 'addProductByTemplate']);
        Route::post('/product-templates/copy/{id}', [ProductTemplateController::class, 'duplicate']);
    });


    Route::post('/cart', [CartController::class, 'store']);
    Route::get('/cart/count', [CartController::class, 'countItems']);
    Route::get('/cart', [CartController::class, 'getCartItems']);
    Route::post('/cart/update-quantity', [CartController::class, 'updateQuantity']);
    Route::delete('/cart/{id}', [CartController::class, 'removeFromCart']);
    Route::get('/customer', [CustomerController::class, 'getCurrentCustomer']);
    Route::post('/profile', [CustomerController::class, 'updateCurrentUser']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::delete('/cart', [CartController::class, 'clearCart']);
    Route::get('/orders', [OrderController::class, 'getCustomerOrders']);
    Route::get('/shippings', [ShippingController::class, 'index']);
    Route::post('/shippings', [ShippingController::class, 'store']);

    Route::post('/follow', [FollowController::class, 'follow']);
    Route::post('/unfollow', [FollowController::class, 'unfollow']);
    Route::get('/check-follow', [FollowController::class, 'checkFollow']);
    Route::get('products/{id}', [ProductController::class, 'show']);
});

Route::get('profile-shop/{sellerId}', [ProfileShopController::class, 'getShopInfo']);
Route::post('/orders', [OrderController::class, 'store']);





Route::get('/products/recently-viewed/{customerId}', [ProductController::class, 'getRecentlyViewedProducts']);
Route::get('/categories/parent', [CategoryController::class, 'getParentCategories']);
Route::get('/products/slug/{slug}', [ProductController::class, 'getProductDetailBySlug']);
Route::get('/products/related/{productId}', [ProductController::class, 'getRelatedProducts']);
Route::get('/products/same-seller/{productId}', [ProductController::class, 'getProductsBySameSeller']);
Route::get('products/seller/{sellerId}', [ProductController::class, 'getProductsBySeller']);



Route::get('discounts/seller/{sellerId}', [DiscountController::class, 'getDiscountsBySeller']);


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
Route::get('products-search', [ProductController::class, 'search']);
Route::post('calculateShippingCost', [ShippingCategoryController::class, 'calculateShippingCost']);
Route::get('/products-new', [ProductController::class, 'getNewProducts']);
Route::get('/products-best-selling', [ProductController::class, 'getBestSellingProducts']);
Route::get('/products-detail/{slug}', [ProductController::class, 'getProductDetailBySlug']);
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
Route::post('send-mail', [MailController::class, 'sendMail']);

Route::apiResource('product-templates', ProductTemplateController::class);
