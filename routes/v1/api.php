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
use App\Http\Controllers\Api\V1\User\AddressController;
use App\Http\Controllers\Api\V1\User\Notifications\UserNotificationController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\User\Commerce\CartController;
use App\Http\Controllers\Api\V1\User\Commerce\OrderController;
use App\Http\Controllers\Api\V1\User\Commerce\ReviewController;
use App\Http\Controllers\Api\V1\User\WalletController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductCategoryController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductController;
use App\Http\Controllers\Api\V1\Vendor\SubscriptionController;
use App\Http\Controllers\Api\V1\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\CouponController;
use App\Http\Controllers\Api\V1\User\Commerce\DiscoveryController;
use App\Http\Controllers\Api\V1\User\Commerce\FavouriteController;
use App\Http\Controllers\Api\V1\User\PaymentMethodController;
use App\Http\Controllers\Api\V1\Vendor\VendorController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystackWebhook']);

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
        Route::post('/setup-vendor-profile', [VendorController::class, 'setUpVendorProfile'])->name('user.setup.vendor.profile');
        Route::post('/create-password', [UserController::class, 'createPassword'])->name('user.update.password');
        Route::post('/update-avatar', [UserController::class, 'updateAvatar'])->name('user.update.avatar');
        Route::put('/update-profile', [UserController::class, 'updateProfile'])->name('user.update.profile');
        Route::post('/update-vendor-profile', [UserController::class, 'updateVendorProfile'])->name('user.update.vendor.profile');
        Route::post('/delete-account', [UserController::class, 'deleteAccount'])->name('user.delete.account');
    });

    Route::middleware(['user.is.vendor'])->prefix('vendor')->group(function () {
        Route::prefix('details')->group(function () {
            Route::get('/', [VendorController::class, 'getVendorDetails'])->name('user.vendor.details');
            Route::put('/update', [VendorController::class, 'updateVendorDetails'])->name('user.vendor.update.details');
        });

        Route::prefix('product-categories')->group(function () {
            Route::get('/', [ProductCategoryController::class, 'index'])->name('user.vendor.product.categories.list');
            Route::post('/', [ProductCategoryController::class, 'store'])->name('user.vendor.product.category.create');
            Route::post('/{id}', [ProductCategoryController::class, 'update'])->name('user.vendor.product.category.update');
            Route::delete('/{id}', [ProductCategoryController::class, 'delete'])->name('user.vendor.product.category.delete');
        });
        
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index'])->name('user.vendor.products.list');
            Route::middleware('vendor.subscription.product.listing')->post('/', [ProductController::class, 'store'])->name('user.vendor.product.create');
            Route::post('/{id}', [ProductController::class, 'update'])->name('user.vendor.product.update');
            Route::delete('/{id}', [ProductController::class, 'delete'])->name('user.vendor.product.delete');
        });

        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [SubscriptionController::class, 'fetchVendorSubscription'])->name('vendor.get.subscription');
            Route::get('/plans', [SubscriptionController::class, 'getPlans'])->name('vendor.subscription.plans');
            Route::get('/plans/{id}', [SubscriptionController::class, 'fetchPlan'])->name('vendor.subscription.plan.show');
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('vendor.subscription.subscribe');
            Route::post('/upgrade', [SubscriptionController::class, 'upgradeSubscription'])->name('vendor.upgrade.subscription');
            Route::post('/update-payment-method', [SubscriptionController::class, 'updatePaymentMethod'])->name('vendor.update.payment.method.subscription');
            Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('vendor.cancel.subscription');
            // Route::post('/resume', [SubscriptionController::class, 'resumeSubscription'])->name('vendor.resume.subscription');
        });

        Route::prefix('orders')->group(function () {
            Route::get('/', [VendorOrderController::class, 'index'])->name('user.vendor.orders.index');
            Route::get('/{orderId}', [VendorOrderController::class, 'show'])->name('user.vendor.orders.show');
            Route::put('/{orderId}/status', [VendorOrderController::class, 'updateStatus'])->name('user.vendor.orders.update.status');
        });

        Route::prefix('coupons')->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('user.vendor.coupons.index');
            Route::post('/', [CouponController::class, 'store'])->name('user.vendor.coupons.store');
            Route::get('/{id}', [CouponController::class, 'show'])->name('user.vendor.coupons.show');
            Route::put('/{id}', [CouponController::class, 'update'])->name('user.vendor.coupons.update');
            Route::delete('/{id}', [CouponController::class, 'destroy'])->name('user.vendor.coupons.destroy');
        });
    });
    
    Route::middleware(['user.is.not.vendor'])->group(function () {
        Route::prefix('addresses')->group(function () {
            Route::get('/', [AddressController::class, 'index'])->name('user.addresses.list');
            Route::post('/', [AddressController::class, 'store'])->name('user.addresses.add');
            Route::put('/{id}', [AddressController::class, 'update'])->name('user.addresses.update');
            Route::delete('/{id}', [AddressController::class, 'destroy'])->name('user.addresses.delete');
        });

        Route::prefix('discovery')->group(function () {
            Route::get('/vendors/nearby', [DiscoveryController::class, 'nearbyVendors'])->name('user.discovery.vendors.nearby');
            Route::get('/products/nearby', [DiscoveryController::class, 'nearbyProducts'])->name('user.discovery.products.nearby');
            Route::post('/waitlist/join', [DiscoveryController::class, 'joinWaitlist'])->name('user.discovery.waitlist.join');
    
            Route::prefix('searches')->group(function () {
                Route::get('/products', [DiscoveryController::class, 'searchProducts'])->name('user.searches.products');
                Route::get('/vendors', [DiscoveryController::class, 'searchVendors'])->name('user.searches.vendors');
                Route::get('/recent', [DiscoveryController::class, 'recentSearches'])->name('user.searches.recent');
            });

            Route::get('/vendors/{vendorId}', [DiscoveryController::class, 'vendorDetails'])->name('user.discovery.vendors.details');
            Route::get('/products/{productId}', [DiscoveryController::class, 'productDetails'])->name('user.discovery.products.details');
        });

        Route::prefix('favourites')->group(function () {
            Route::prefix('vendors')->group(function () {
                Route::get('/', [FavouriteController::class, 'favouriteVendors'])->name('user.favourites.vendors');
                Route::post('/{vendorId}', [FavouriteController::class, 'addFavouriteVendor'])->name('user.favourites.vendors.add');
                Route::delete('/{vendorId}', [FavouriteController::class, 'removeFavouriteVendor'])->name('user.favourites.vendors.remove');
            });

            Route::prefix('products')->group(function () {
                Route::get('/', [FavouriteController::class, 'favouriteProducts'])->name('user.favourites.products');
                Route::post('/{productId}', [FavouriteController::class, 'addFavouriteProduct'])->name('user.favourites.products.add');
                Route::delete('/{productId}', [FavouriteController::class, 'removeFavouriteProduct'])->name('user.favourites.products.remove');
            });
        });

        Route::prefix('cart')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('user.cart.index');
            Route::get('/vendor/{vendorId}', [CartController::class, 'vendorCart'])->name('user.cart.vendor');
            Route::delete('/vendor/{vendorId}', [CartController::class, 'clearVendorCart'])->name('user.cart.vendor.clear');
            Route::post('/add', [CartController::class, 'addItem'])->name('user.cart.add');
            Route::put('/item/{itemId}', [CartController::class, 'updateItem'])->name('user.cart.update.item');
            Route::delete('/item/{itemId}', [CartController::class, 'removeItem'])->name('user.cart.remove.item');
            Route::delete('/clear', [CartController::class, 'clearCart'])->name('user.cart.clear');
            Route::post('/coupon/{vendorId}/apply', [CartController::class, 'applyCoupon'])->name('user.cart.coupon.apply');
            Route::delete('/coupon/{vendorId}/remove', [CartController::class, 'removeCoupon'])->name('user.cart.coupon.remove');
            Route::post('/coupon/{vendorId}/validate', [CartController::class, 'validateCoupon'])->name('user.cart.coupon.validate');
            Route::post('/process', [CartController::class, 'processCart'])->name('user.cart.process');
        });
    
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('user.orders.index');
            Route::get('/{orderId}', [OrderController::class, 'show'])->name('user.orders.show');
        });

        Route::prefix('reviews')->group(function () {
            Route::get('/{id}', [ReviewController::class, 'index'])->name('user.reviews.index');
            Route::post('', [ReviewController::class, 'store'])->name('user.reviews.store');
        });
        
        Route::prefix('payment-methods')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('user.payment.methods.index');
            Route::post('/', [PaymentMethodController::class, 'initialize'])->name('user.payment.methods.initialize');
            Route::delete('/{id}', [PaymentMethodController::class, 'destroy'])->name('user.payment.methods.destroy');
        });            
    });


    Route::prefix('wallet')->group(function () {
        Route::get('/balance', [WalletController::class, 'balance'])->name('user.wallet.balance');
        Route::post('/deposit', [WalletController::class, 'deposit'])->name('user.wallet.deposit');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('user.wallet.transactions');
        
        // Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('user.wallet.withdraw');
        // Route::post('/transfer', [WalletController::class, 'transfer'])->name('user.wallet.transfer');
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
});