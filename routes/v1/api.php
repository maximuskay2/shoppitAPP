<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Auth\CheckEmailController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\ResendRegisterOtp;
use App\Http\Controllers\Api\V1\Auth\ResetPasswordController;
use App\Http\Controllers\Api\V1\Auth\RefreshTokenController;
use App\Http\Controllers\Api\V1\Auth\VerifyRegisterOtp;
use App\Http\Controllers\Api\V1\Otp\UserOtpController;
use App\Http\Controllers\Api\V1\User\AddressController;
use App\Http\Controllers\Api\V1\User\Notifications\UserNotificationController;
use App\Http\Controllers\Api\V1\User\UserController;
use App\Http\Controllers\Api\V1\User\Commerce\CartController;
use App\Http\Controllers\Api\V1\User\Commerce\OrderController;
use App\Http\Controllers\Api\V1\User\Commerce\OrderTrackingController;
use App\Http\Controllers\Api\V1\User\Commerce\ReviewController;
use App\Http\Controllers\Api\V1\User\NotificationPreferencesController;
use App\Http\Controllers\Api\V1\User\WalletController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductCategoryController;
use App\Http\Controllers\Api\V1\Vendor\Products\ProductController;
use App\Http\Controllers\Api\V1\Vendor\SubscriptionController;
use App\Http\Controllers\Api\V1\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\V1\Vendor\OrderTrackingController as VendorOrderTrackingController;
use App\Http\Controllers\Api\V1\Vendor\CouponController;
use App\Http\Controllers\Api\V1\Vendor\EarningController as VendorEarningController;
use App\Http\Controllers\Api\V1\Vendor\PayoutController as VendorPayoutController;
use App\Http\Controllers\Api\V1\Vendor\StoreController as VendorStoreController;
use App\Http\Controllers\Api\V1\User\Commerce\DiscoveryController;
use App\Http\Controllers\Api\V1\User\Commerce\FavouriteController;
use App\Http\Controllers\Api\V1\User\PaymentMethodController;
use App\Http\Controllers\Api\V1\Vendor\PaymentDetailsController;
use App\Http\Controllers\Api\V1\Vendor\VendorController;
use App\Http\Controllers\Api\V1\Vendor\PromotionController as VendorPromotionController;
use App\Http\Controllers\Api\V1\Driver\AuthController as DriverAuthController;
use App\Http\Controllers\Api\V1\Driver\DriverDocumentController as DriverDocumentController;
use App\Http\Controllers\Api\V1\Driver\EarningController as DriverEarningController;
use App\Http\Controllers\Api\V1\Driver\DriverPayoutController as DriverPayoutController;
use App\Http\Controllers\Api\V1\Driver\ProfileController as DriverProfileController;
use App\Http\Controllers\Api\V1\Driver\PaymentDetailsController as DriverPaymentDetailsController;
use App\Http\Controllers\Api\V1\Driver\StatusController as DriverStatusController;
use App\Http\Controllers\Api\V1\Driver\FcmTokenController as DriverFcmTokenController;
use App\Http\Controllers\Api\V1\Driver\DriverVehicleController as DriverVehicleController;
use App\Http\Controllers\Api\V1\Driver\SupportTicketController as DriverSupportTicketController;
use App\Http\Controllers\Api\V1\Driver\NavigationController as DriverNavigationController;
use App\Http\Controllers\Api\V1\Driver\StatsController as DriverStatsController;
use App\Http\Controllers\Api\V1\Driver\OrderProofController as DriverOrderProofController;
use App\Http\Controllers\Commerce\BlogController;
use App\Http\Controllers\Commerce\PromotionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystackWebhook']);

Route::get('/delivery-zones/check', [\App\Http\Controllers\Api\V1\DeliveryZoneCheckController::class, 'check'])
    ->name('delivery-zones.check');
Route::get('/delivery-zones', [\App\Http\Controllers\Api\V1\DeliveryZoneListController::class, 'index'])
    ->name('delivery-zones.index');

Route::prefix('auth')->group(function () {
    Route::post('/check-email', CheckEmailController::class);
    Route::middleware('throttle:login')->post('/register', RegisterController::class);
    Route::middleware('throttle:login')->post('/login', LoginController::class);
    Route::middleware('throttle:login')->post('/refresh', RefreshTokenController::class);
    Route::post('/logout', LogoutController::class)->middleware('auth:sanctum');
    Route::middleware('throttle:otp')->post('/resend-register-otp', ResendRegisterOtp::class)->name('auth.resend.register.otp');
    Route::middleware('throttle:otp')->post('/verify-register-otp', VerifyRegisterOtp::class)->name('auth.verify.register.otp');
    Route::middleware('throttle:otp')->post('/send-code', [UserOtpController::class, 'send'])->name('auth.send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [UserOtpController::class, 'verify'])->name('auth.verify.otp');
    Route::middleware('throttle:login')->post('/reset-password', ResetPasswordController::class)->name('auth.reset.password');
});

Route::middleware(['auth:sanctum', 'user.is.active', 'user.is.email.verified'])->prefix('user')->group(function () {
    Route::prefix('account')->group(function () {
        Route::get('/', [UserController::class, 'getAuthentictedUser'])->name('user.show.account');
        Route::post('/setup-profile', [UserController::class, 'setUpProfile'])->name('user.setup.profile');
        Route::post('/setup-vendor-profile', [VendorController::class, 'setUpVendorProfile'])->name('user.setup.vendor.profile');
        Route::post('/create-password', [UserController::class, 'createPassword'])->name('user.update.password');
        Route::post('/update-avatar', [UserController::class, 'updateAvatar'])->name('user.update.avatar');
        Route::put('/update-profile', [UserController::class, 'updateProfile'])->name('user.update.profile');
        Route::post('/update-vendor-profile', [UserController::class, 'updateVendorProfile'])->name('user.update.vendor.profile');
        Route::post('/delete-account', [UserController::class, 'deleteAccount'])->name('user.delete.account');
        Route::get('/export-my-data', [UserController::class, 'exportMyData'])->name('user.export.my.data');
        Route::post('/logout', LogoutController::class)->name('user.logout');
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
            Route::post('/{id}/duplicate', [ProductController::class, 'duplicate'])->name('user.vendor.product.duplicate');
        });

        Route::prefix('coupons')->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('user.vendor.coupons.index');
            Route::post('/', [CouponController::class, 'store'])->name('user.vendor.coupons.store');
            Route::get('/{id}', [CouponController::class, 'show'])->name('user.vendor.coupons.show');
            Route::put('/{id}', [CouponController::class, 'update'])->name('user.vendor.coupons.update');
            Route::delete('/{id}', [CouponController::class, 'destroy'])->name('user.vendor.coupons.destroy');
        });

        Route::prefix('subscriptions')->group(function () {
            Route::get('/', [SubscriptionController::class, 'fetchVendorSubscription'])->name('vendor.get.subscription');
            Route::get('/plans', [SubscriptionController::class, 'getPlans'])->name('vendor.subscription.plans');
            Route::get('/plans/{id}', [SubscriptionController::class, 'fetchPlan'])->name('vendor.subscription.plan.show');
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('vendor.subscription.subscribe');
            Route::post('/upgrade', [SubscriptionController::class, 'upgradeSubscription'])->name('vendor.upgrade.subscription');
            Route::post('/update-payment-method', [SubscriptionController::class, 'updatePaymentMethod'])->name('vendor.update.payment.method.subscription');
            Route::post('/cancel', [SubscriptionController::class, 'cancelSubscription'])->name('vendor.cancel.subscription');            
        });

        Route::get('vendor/analytics/summary', [\App\Http\Controllers\Api\V1\User\VendorAnalyticsController::class, 'summary'])->name('user.vendor.analytics.summary');
        Route::prefix('orders')->group(function () {
            Route::get('/', [VendorOrderController::class, 'index'])->name('user.vendor.orders.index');
            Route::get('/{orderId}', [VendorOrderController::class, 'show'])->name('user.vendor.orders.show');
            Route::put('/{orderId}/status', [VendorOrderController::class, 'updateStatus'])->name('user.vendor.orders.update.status');
            Route::get('/{orderId}/track', [VendorOrderTrackingController::class, 'track'])->name('user.vendor.orders.track');
            Route::get('/{orderId}/eta', [VendorOrderTrackingController::class, 'eta'])->name('user.vendor.orders.eta');
            Route::get('/settlements/list', [VendorOrderController::class, 'settlements'])->name('user.vendor.orders.settlements');
            Route::get('/statistics/summary', [VendorOrderController::class, 'orderStatisticsSummary'])->name('user.vendor.orders.statistics.summary');
        });

        Route::get('/earnings/summary', [VendorEarningController::class, 'summary'])
            ->name('user.vendor.earnings.summary');

        Route::prefix('payouts')->group(function () {
            Route::get('/', [VendorPayoutController::class, 'index'])->name('user.vendor.payouts.index');
            Route::post('/withdraw', [VendorPayoutController::class, 'withdraw'])
                ->name('user.vendor.payouts.withdraw');
        });

        Route::prefix('store')->group(function () {
            Route::put('/status', [VendorStoreController::class, 'updateStatus'])
                ->name('user.vendor.store.status');
            Route::put('/hours', [VendorStoreController::class, 'updateHours'])
                ->name('user.vendor.store.hours');
        });

        Route::prefix('payment-details')->group(function () {
            Route::get('/', [PaymentDetailsController::class, 'index'])->name('user.payment.details.index');
            Route::get('/banks', [PaymentDetailsController::class, 'listBanks'])->name('user.payment.details.banks');
            Route::post('/resolve-account', [PaymentDetailsController::class, 'resolveAccount'])->name('user.payment.details.resolve.account');
            Route::post('/', [PaymentDetailsController::class, 'store'])->name('user.payment.details.store');
            Route::delete('/{id}', [PaymentDetailsController::class, 'destroy'])->name('user.payment.details.destroy');
        });

        Route::prefix('promotions')->group(function () {
            Route::get('/', [VendorPromotionController::class, 'myPromotions'])->name('user.vendor.promotions.my.requests');            
            Route::get('/{id}', [VendorPromotionController::class, 'show'])->name('user.vendor.promotions.show');
            Route::post('/request', [VendorPromotionController::class, 'requestPromotion'])->name('user.vendor.promotions.request');
            Route::delete('/{id}', [VendorPromotionController::class, 'cancelRequest'])->name('user.vendor.promotions.cancel');
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
            Route::put('/{orderId}/status', [OrderController::class, 'updateStatus'])->name('user.orders.update.status');
            Route::post('/{orderId}/cancel', [OrderController::class, 'cancel'])->name('user.orders.cancel');
            Route::post('/{orderId}/refund-request', [OrderController::class, 'refundRequest'])->name('user.orders.refund.request');
            Route::get('/{orderId}/refund-status', [OrderController::class, 'refundStatus'])->name('user.orders.refund.status');
            Route::post('/{orderId}/rate-driver', [OrderController::class, 'rateDriver'])->name('user.orders.rate.driver');
            Route::get('/{orderId}/track', [OrderTrackingController::class, 'track'])->name('user.orders.track');
            Route::get('/{orderId}/eta', [OrderTrackingController::class, 'eta'])->name('user.orders.eta');
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
        Route::middleware(['user.is.not.vendor'])->post('/deposit', [WalletController::class, 'deposit'])->name('user.wallet.deposit');
        Route::get('/transactions', [WalletController::class, 'transactions'])->name('user.wallet.transactions');
        Route::middleware(['user.is.vendor'])->post('/withdraw', [WalletController::class, 'withdraw'])->name('user.wallet.withdraw');
    });

    Route::prefix('settings')->group(function () {
        Route::get('/notifications', [NotificationPreferencesController::class, 'show'])
            ->name('user.settings.notifications.show');
        Route::put('/notifications', [NotificationPreferencesController::class, 'update'])
            ->name('user.settings.notifications.update');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [UserNotificationController::class, 'index'])->name('user.notifications.index');
        Route::get('/unread-count', [UserNotificationController::class, 'unreadCount'])->name('user.notifications.unread.count');
        Route::get('/{id}', [UserNotificationController::class, 'show'])->name('user.notifications.show');
        Route::post('/{id}/read', [UserNotificationController::class, 'markRead'])->name('user.notifications.read');
        Route::post('/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('user.notifications.read.all');
        Route::delete('/{id}', [UserNotificationController::class, 'destroy'])->name('user.notifications.delete');
        Route::delete('/', [UserNotificationController::class, 'destroyAll'])->name('user.notifications.delete.all');

        // Unified notification endpoints
        Route::prefix('unified')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])->name('unified.notifications.index');
            Route::post('/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead'])->name('unified.notifications.read');
            Route::post('/{id}/unread', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsUnread'])->name('unified.notifications.unread');
            Route::post('/send', [\App\Http\Controllers\Api\V1\NotificationController::class, 'send'])->name('unified.notifications.send');
        });
    });

    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index'])->name('promotions.active');
    });
});

Route::prefix('driver/auth')->group(function () {
    Route::post('/register', [DriverAuthController::class, 'register'])->name('driver.auth.register');
    Route::post('/login', [DriverAuthController::class, 'login'])->name('driver.auth.login');
    Route::post('/login-otp', [DriverAuthController::class, 'loginWithOtp'])->name('driver.auth.login.otp');
});

Route::middleware(['auth:driver'])->prefix('driver')->group(function () {
    Route::get('/app-config', [\App\Http\Controllers\Api\V1\Driver\AppConfigController::class, 'show'])
        ->name('driver.app.config');
});

Route::middleware(['auth:driver', 'user.is.active', 'user.is.email.verified', 'user.has.driver'])->prefix('driver')->group(function () {
    Route::get('/profile', [DriverProfileController::class, 'show'])->name('driver.profile.show');
    Route::put('/profile', [DriverProfileController::class, 'update'])->name('driver.profile.update');
    Route::post('/profile/avatar', [DriverProfileController::class, 'updateAvatar'])->name('driver.profile.avatar');
    Route::post('/profile/password', [DriverProfileController::class, 'changePassword'])->name('driver.profile.password');
    Route::get('/vehicles', [DriverVehicleController::class, 'index'])->name('driver.vehicles.index');
    Route::post('/vehicles', [DriverVehicleController::class, 'store'])->name('driver.vehicles.store');
    Route::put('/vehicles/{id}', [DriverVehicleController::class, 'update'])->name('driver.vehicles.update');
    Route::delete('/vehicles/{id}', [DriverVehicleController::class, 'destroy'])->name('driver.vehicles.destroy');
    Route::post('/fcm-token', [DriverFcmTokenController::class, 'store'])->name('driver.fcm.token');
    Route::get('/documents', [DriverDocumentController::class, 'index'])->name('driver.documents.index');
    Route::post('/documents', [DriverDocumentController::class, 'store'])->name('driver.documents.store');
    Route::get('/payment-details', [DriverPaymentDetailsController::class, 'show'])->name('driver.payment.details.show');
    Route::get('/payment-details/banks', [DriverPaymentDetailsController::class, 'listBanks'])->name('driver.payment.details.banks');
    Route::post('/payment-details/resolve-account', [DriverPaymentDetailsController::class, 'resolveAccount'])->name('driver.payment.details.resolve');
    Route::post('/payment-details', [DriverPaymentDetailsController::class, 'store'])->name('driver.payment.details.store');
});

Route::middleware(['auth:driver', 'user.is.active', 'user.is.email.verified', 'user.is.driver'])->prefix('driver')->group(function () {
    Route::get('/orders/available', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'available'])->name('driver.orders.available');
    Route::post('/orders/{orderId}/accept', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'accept'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.accept');
    Route::post('/orders/{orderId}/reject', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'reject'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.reject');
    Route::post('/orders/{orderId}/pickup', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'pickup'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.pickup');
    Route::post('/orders/{orderId}/out-for-delivery', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'startDelivery'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.out.for.delivery');
    Route::post('/orders/{orderId}/deliver', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'deliver'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.deliver');
    Route::post('/orders/{orderId}/pod', [DriverOrderProofController::class, 'store'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.pod');
    Route::post('/orders/{orderId}/cancel', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'cancel'])
        ->middleware('throttle:driver-actions')
        ->name('driver.orders.cancel');

    Route::post('/sos', [\App\Http\Controllers\Api\V1\Driver\SosController::class, 'store'])
        ->middleware('throttle:driver-actions')
        ->name('driver.sos');

    Route::prefix('notifications')->group(function () {
        Route::prefix('unified')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index'])
                ->name('driver.notifications.unified.index');
            Route::post('/{id}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsRead'])
                ->name('driver.notifications.unified.read');
            Route::post('/{id}/unread', [\App\Http\Controllers\Api\V1\NotificationController::class, 'markAsUnread'])
                ->name('driver.notifications.unified.unread');
            Route::post('/send', [\App\Http\Controllers\Api\V1\NotificationController::class, 'send'])
                ->name('driver.notifications.unified.send');
        });
    });
    Route::get('/orders/active', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'active'])->name('driver.orders.active');
    Route::get('/orders/history', [\App\Http\Controllers\Api\V1\Driver\OrderController::class, 'history'])->name('driver.orders.history');
    Route::get('/earnings', [DriverEarningController::class, 'summary'])->name('driver.earnings.summary');
    Route::get('/earnings/history', [DriverEarningController::class, 'history'])->name('driver.earnings.history');
    Route::get('/payouts', [DriverPayoutController::class, 'index'])->name('driver.payouts.index');
    Route::get('/payouts/balance', [DriverPayoutController::class, 'balance'])->name('driver.payouts.balance');
    Route::post('/payouts/request', [DriverPayoutController::class, 'request'])
        ->middleware('throttle:driver-actions')
        ->name('driver.payouts.request');
    Route::get('/stats', [DriverStatsController::class, 'summary'])->name('driver.stats.summary');
    Route::post('/status', [DriverStatusController::class, 'update'])
        ->middleware('throttle:driver-status')
        ->name('driver.status.update');
    Route::post('/location', [\App\Http\Controllers\Api\V1\Driver\LocationController::class, 'store'])
        ->middleware('throttle:location')
        ->name('driver.location');
    Route::post('/location-update', [\App\Http\Controllers\Api\V1\Driver\LocationController::class, 'store'])
        ->middleware('throttle:location')
        ->name('driver.location.update');
    Route::get('/support/tickets', [DriverSupportTicketController::class, 'index'])->name('driver.support.tickets.index');
    Route::post('/support/tickets', [DriverSupportTicketController::class, 'store'])->name('driver.support.tickets.store');
    Route::get('/support/tickets/{id}', [DriverSupportTicketController::class, 'show'])->name('driver.support.tickets.show');
    Route::post('/navigation/route', [DriverNavigationController::class, 'route'])->name('driver.navigation.route');
    Route::get('/ratings', [\App\Http\Controllers\Api\V1\Driver\RatingController::class, 'summary'])->name('driver.ratings.summary');
});

// Public blog endpoints (no authentication required)
Route::prefix('blogs')->group(function () {
    Route::get('/categories', [BlogController::class, 'indexCategories'])->name('blogs.categories.index');
    Route::get('/', [BlogController::class, 'index'])->name('blogs.index');
    Route::get('/{id}', [BlogController::class, 'show'])->name('blogs.show');
});