<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\CheckEmailController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\ResendRegisterOtp;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\VerifyRegisterOtp;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Http\Controllers\Api\V1\User\Notifications\UserNotificationController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\User\CartController;
use App\Http\Controllers\Api\V1\User\OrderController;
use App\Http\Controllers\Api\V1\User\ReviewController;
use App\Http\Controllers\Api\V1\User\WalletController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductCategoryController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductController;
use App\Http\Controllers\Api\V1\Vendor\SubscriptionController;
use App\Http\Controllers\Api\V1\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\CouponController;
use App\Http\Controllers\Api\V1\User\DiscoveryController;
use Illuminate\Support\Facades\Route;


Route::prefix('auth')->group(function () {
    Route::post('/check-email', CheckEmailController::class);
    Route::middleware('throttle:login')->post('/register', RegisterController::class);
    Route::middleware('throttle:login')->post('/login', LoginController::class);
    Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
    Route::middleware('throttle:otp')->post('/resend-register-otp', ResendRegisterOtp::class)->name('auth.resend.register.otp');
    Route::middleware('throttle:otp')->post('/verify-register-otp', VerifyRegisterOtp::class)->name('auth.verify.register.otp');
    Route::middleware('throttle:otp')->post('/send-code', [UserOtpController::class, 'send'])->name('auth.send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [UserOtpController::class, 'verify'])->name('auth.verify.otp');
    Route::middleware('throttle:login')->post('/reset-password', ResetPasswordController::class)->name('auth.reset.password');
});



Route::middleware(['auth:sanctum', 'user.is.email.verified'])->prefix('user')->group(function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [UserController::class, 'getAuthentictedUser'])->name('user.show.account');
        Route::post('/setup-profile', [UserController::class, 'setUpProfile'])->name('user.setup.profile');
        Route::post('/setup-vendor-profile', [UserController::class, 'setUpVendorProfile'])->name('user.setup.vendor.profile');
        Route::post('/create-password', [UserController::class, 'createPassword'])->name('user.update.password');
        Route::post('/update-avatar', [UserController::class, 'updateAvatar'])->name('user.update.avatar');
        Route::put('/update-profile', [UserController::class, 'updateProfile'])->name('user.update.profile');
        Route::post('/update-vendor-profile', [UserController::class, 'updateVendorProfile'])->name('user.update.vendor.profile');
        Route::post('/delete-account', [UserController::class, 'deleteAccount'])->name('user.delete.account');
    });

    Route::middleware(['user.is.vendor'])->prefix('vendor')->group(function () {
        Route::prefix('product-categories')->group(function () {
            Route::get('/', [ProductCategoryController::class, 'index'])->name('user.vendor.product.categories.list');
            Route::post('/', [ProductCategoryController::class, 'store'])->name('user.vendor.product.category.create');
            Route::post('/{id}', [ProductCategoryController::class, 'update'])->name('user.vendor.product.category.update');
            Route::delete('/{id}', [ProductCategoryController::class, 'delete'])->name('user.vendor.product.category.delete');
        });
        
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('user.vendor.products.list');
            Route::post('/', [ProductController::class, 'store'])->name('user.vendor.product.create');
            Route::post('/{id}', [ProductController::class, 'update'])->name('user.vendor.product.update');
            Route::delete('/{id}', [ProductController::class, 'delete'])->name('user.vendor.product.delete');
        });

        Route::prefix('subscriptions')->group(function () {
            Route::get('/plans', [SubscriptionController::class, 'getPlans'])->name('user.vendor.subscription.plans');
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('user.vendor.subscription.subscribe');
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [VendorOrderController::class, 'index'])->name('user.vendor.orders.index');
            Route::get('/{orderId}', [VendorOrderController::class, 'show'])->name('user.vendor.orders.show');
            Route::put('/{orderId}/status', [VendorOrderController::class, 'updateStatus'])->name('user.vendor.orders.update.status');
        });

        Route::prefix('coupons')->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('user.vendor.coupons.index');
            Route::post('/', [CouponController::class, 'store'])->name('user.vendor.coupons.store');
            Route::get('/{coupon}', [CouponController::class, 'show'])->name('user.vendor.coupons.show');
            Route::put('/{coupon}', [CouponController::class, 'update'])->name('user.vendor.coupons.update');
            Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('user.vendor.coupons.destroy');
            Route::patch('/{coupon}/toggle-visibility', [CouponController::class, 'toggleVisibility'])->name('user.vendor.coupons.toggle.visibility');
            Route::patch('/{coupon}/toggle-active', [CouponController::class, 'toggleActive'])->name('user.vendor.coupons.toggle.active');
        });
    });
    
    
    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index'])->name('user.notifications.index');
        Route::get('/unread-count', [UserNotificationController::class, 'unreadCount'])->name('user.notifications.unread.count');
        Route::get('/{id}', [UserNotificationController::class, 'show'])->name('user.notifications.show');
        Route::post('/{id}/read', [UserNotificationController::class, 'markRead'])->name('user.notifications.read');
        Route::post('/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('user.notifications.read.all');
        Route::delete('/{id}', [UserNotificationController::class, 'destroy'])->name('user.notifications.delete');
        Route::delete('/', [UserNotificationController::class, 'destroyAll'])->name('user.notifications.delete.all');
    });

    Route::prefix('discovery')->group(function () {
        Route::get('/vendors/nearby', [DiscoveryController::class, 'nearbyVendors'])->name('user.discovery.vendors.nearby');
        Route::get('/products/new', [DiscoveryController::class, 'newProducts'])->name('user.discovery.products.new');
        Route::post('/waitlist/join', [DiscoveryController::class, 'joinWaitlist'])->name('user.discovery.waitlist.join');
        Route::get('/searches/recent', [DiscoveryController::class, 'recentSearches'])->name('user.discovery.searches.recent');
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('user.cart.index');
        Route::post('/add', [CartController::class, 'addItem'])->name('user.cart.add');
        Route::put('/item/{itemId}', [CartController::class, 'updateItem'])->name('user.cart.update.item');
        Route::delete('/item/{itemId}', [CartController::class, 'removeItem'])->name('user.cart.remove.item');
        Route::delete('/clear', [CartController::class, 'clearCart'])->name('user.cart.clear');
        Route::post('/process', [CartController::class, 'processCart'])->name('user.cart.process');
    });

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('user.orders.index');
        Route::get('/{orderId}', [OrderController::class, 'show'])->name('user.orders.show');
    });

    Route::prefix('reviews')->group(function () {
        Route::get('/vendors/{vendor}', [ReviewController::class, 'index'])->name('user.reviews.vendor.index');
        Route::post('/vendors/{vendor}', [ReviewController::class, 'store'])->name('user.reviews.vendor.store');
        Route::put('/{review}', [ReviewController::class, 'update'])->name('user.reviews.update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('user.reviews.destroy');
    });

    Route::prefix('wallet')->group(function () {
        Route::get('/dashboard', [WalletController::class, 'dashboard'])->name('user.wallet.dashboard');
        Route::get('/balance', [WalletController::class, 'balance'])->name('user.wallet.balance');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('user.wallet.transactions');
        // Route::post('/deposit', [WalletController::class, 'deposit'])->name('user.wallet.deposit');
        // Route::post('/transfer', [WalletController::class, 'transfer'])->name('user.wallet.transfer');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('user.wallet.withdraw');
    });
});