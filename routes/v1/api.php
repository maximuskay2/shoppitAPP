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
        Route::post('/setup-profile', [UserController::class, 'setUpProfile'])->name('user.update.profile');
        Route::post('/setup-vendor-profile', [UserController::class, 'setUpVendorProfile'])->name('user.update.vendor.profile');
        Route::post('/create-password', [UserController::class, 'createPassword'])->name('user.update.password');
        Route::post('/update-avatar', [UserController::class, 'updateAvatar'])->name('user.update.avatar');
        Route::put('/update-profile', [UserController::class, 'updateProfile'])->name('user.update.profile');
        Route::post('/update-vendor-profile', [UserController::class, 'updateVendorProfile'])->name('user.update.vendor.profile');
        Route::post('/delete-account', [UserController::class, 'deleteAccount'])->name('user.delete.account');
    });
    
    
//     Route::prefix('notifications')->group(function () {
//         Route::get('/', [UserNotificationController::class, 'index'])->name('user.notifications.index');
//         Route::get('/unread-count', [UserNotificationController::class, 'unreadCount'])->name('user.notifications.unread.count');
//         Route::get('/{id}', [UserNotificationController::class, 'show'])->name('user.notifications.show');
//         Route::post('/{id}/read', [UserNotificationController::class, 'markRead'])->name('user.notifications.read');
//         Route::post('/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('user.notifications.read.all');
//         Route::delete('/{id}', [UserNotificationController::class, 'destroy'])->name('user.notifications.delete');
//         Route::delete('/', [UserNotificationController::class, 'destroyAll'])->name('user.notifications.delete.all');
//     });
    
//     Route::prefix('blockchain')->group(function () {
//         Route::prefix('assets')->group(function () {
//             Route::get('/', [BlockchainController::class, 'getAssets']);
//             Route::get('/deposit', [BlockchainController::class, 'getDepositAssets']);
//             // Route::get('/withdraw', [BlockchainController::class, 'getWithdrawAssets']);
//         });
//     });

//     Route::prefix('p2ptrading')->group(function () {
//         Route::get('/currencies', [P2PController::class, 'getP2PCurrencies'])->name('p2p.currencies.index');
//         Route::get('/countries/{currencyId}', [P2PController::class, 'getP2PCountries'])->name('p2p.countries.by.currency');
//         Route::get('/payment-methods/{countryId}', [P2PController::class, 'getP2PPaymentMethodsByCountry'])->name('p2p.payment.methods.by.country');

//         Route::prefix('merchant')->group(function () {
//             Route::prefix('zones')->group(function () {
//                 Route::get('/', [ZoneController::class, 'getZones'])->name('p2p.merchant.zones.index');
//                 Route::get('/continents', [ZoneController::class, 'getContinents'])->name('p2p.merchant.zones.continents');
//                 Route::get('/countries', [ZoneController::class, 'getCountries'])->name('p2p.merchant.zones.countries');
//                 Route::get('/filter', [ZoneController::class, 'filterZones'])->name('p2p.merchant.zones.filter');
//                 Route::get('/{zoneId}', [ZoneController::class, 'getZone'])->name('p2p.merchant.zones.show');
//             });

//             Route::get('/eligibility', [P2PController::class, 'checkEligibility'])->name('p2p.merchant.check.eligibility');
//             Route::post('/apply', [P2PController::class, 'merchantApplication'])->name('p2p.merchant.apply');
//         });

//         Route::prefix('recipient')->group(function () {
//             // Recipient routes will go here
//         });
//     });
});