<?php

// use App\Http\Controllers\Api\V1\Admin\Account\AdminAccountController;
// use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
// use App\Http\Controllers\Api\V1\Admin\AdminServiceManagementController;
// use App\Http\Controllers\Api\V1\Admin\AdminStatisticsController;
// use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
// use App\Http\Controllers\Api\V1\Admin\AdminTransactionController;
// use App\Http\Controllers\Api\V1\Admin\AdminVirtualBankAccountController;
// use App\Http\Controllers\Api\V1\Admin\AdminWalletController;
// use App\Http\Controllers\Api\V1\Admin\AdminLinkedBankAccountController;
// use App\Http\Controllers\Api\V1\Admin\AdminBeneficiaryController;
// use App\Http\Controllers\Api\V1\Admin\AdminSettingsController;
// use App\Http\Controllers\Api\V1\Admin\Auth\AdminLoginController;
// use App\Http\Controllers\Api\V1\Admin\Auth\AdminResetPasswordController;
// use App\Http\Controllers\Api\V1\Admin\Otp\AdminOtpController;
// use App\Http\Controllers\Api\V1\Admin\Notifications\AdminNotificationController;
// use App\Http\Controllers\Api\V1\Admin\UserManagementController;
// use Illuminate\Support\Facades\Route;

// Route::prefix('auth')->name('admin.auth.')->group(function () {
//     Route::middleware('throttle:login')->post('/login', AdminLoginController::class)->name('login');
//     Route::middleware('throttle:otp')->post('/send-code', [AdminOtpController::class, 'send'])->name('send.otp');
//     Route::middleware('throttle:otp')->post('/verify-code', [AdminOtpController::class, 'verify'])->name('verify.otp');
//     Route::middleware('throttle:login')->post('/reset-password', AdminResetPasswordController::class)->name('reset.password');
// });

// Route::middleware(['auth:admin-api', 'admin'])->group(function () {
//     Route::prefix('profile')->group(function () {
//         Route::get('/', [AdminAccountController::class, 'show'])->name('admin.show.account');
//         Route::put('/update', [AdminAccountController::class, 'update'])->name('admin.update.account');
//         Route::post('/update-avatar', [AdminAccountController::class, 'updateAvatar'])->name('admin.update.avatar');
//         Route::post('/change-password', [AdminAccountController::class, 'changePassword'])->name('admin.change.password');
//     });

//     Route::prefix('notifications')->group(function () {
//         Route::get('/', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
//         Route::get('/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('admin.notifications.unread.count');
//         Route::get('/{id}', [AdminNotificationController::class, 'show'])->name('admin.notifications.show');
//         Route::post('/{id}/read', [AdminNotificationController::class, 'markRead'])->name('admin.notifications.read');
//         Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])->name('admin.notifications.read.all');
//         Route::delete('/{id}', [AdminNotificationController::class, 'destroy'])->name('admin.notifications.delete');
//         Route::delete('/', [AdminNotificationController::class, 'destroyAll'])->name('admin.notifications.delete.all');
//     });

//     Route::prefix('user-management')->middleware('user.management.scope')->group(function () {
//         Route::get('/', [UserManagementController::class, 'index'])->name('admin.users.index');
//         Route::get('/stats', [UserManagementController::class, 'stats'])->name('admin.users.stats');
//         Route::post('/', [UserManagementController::class, 'store'])->name('admin.users.store');
//         Route::get('/{id}', [UserManagementController::class, 'show'])->name('admin.users.show');
//         Route::put('/{id}', [UserManagementController::class, 'update'])->name('admin.users.update');
//         Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
//         Route::post('/{id}/suspend', [UserManagementController::class, 'suspend'])->name('admin.users.suspend');
//         Route::post('/{id}/activate', [UserManagementController::class, 'activate'])->name('admin.users.activate');
//         Route::post('/{id}/change-status', [UserManagementController::class, 'changeStatus'])->name('admin.users.change.status');
//         Route::get('/{id}/transactions', [UserManagementController::class, 'transactions'])->name('admin.users.transactions');
//         Route::get('/{id}/wallets', [UserManagementController::class, 'wallet'])->name('admin.users.wallet');
//         Route::get('/{id}/subscriptions', [AdminSubscriptionController::class, 'userSubscriptions'])->name('admin.users.subscriptions');
//         Route::get('/{id}/virtual-bank-accounts', [AdminVirtualBankAccountController::class, 'userVirtualBankAccounts'])->name('admin.users.virtual-bank-accounts');
//         Route::get('/{id}/linked-bank-accounts', [UserManagementController::class, 'linkedBankAccounts'])->name('admin.users.linked-bank-accounts');
//         Route::get('/{id}/beneficiaries', [UserManagementController::class, 'beneficiaries'])->name('admin.users.beneficiaries');
//     });

//     Route::prefix('transactions')->group(function () {
//         Route::get('/', [AdminTransactionController::class, 'index'])->name('admin.transactions.index');
//         Route::get('/{id}', [AdminTransactionController::class, 'show'])->name('admin.transactions.show');
//     });

//     Route::prefix('subscriptions')->group(function () {
//         Route::get('/', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
//         Route::get('/{id}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
//     });

//     Route::prefix('roles')->group(function () {
//         Route::get('/', [AdminRoleController::class, 'index'])->name('admin.roles.index');
//         Route::post('/', [AdminRoleController::class, 'store'])->name('admin.roles.store');
//         Route::get('/{id}', [AdminRoleController::class, 'show'])->name('admin.roles.show');
//         Route::put('/{id}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
//         Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
//     });

//     Route::prefix('virtual-bank-accounts')->group(function () {
//         Route::get('/', [AdminVirtualBankAccountController::class, 'index'])->name('admin.virtual-bank-accounts.index');
//         Route::get('/{id}', [AdminVirtualBankAccountController::class, 'show'])->name('admin.virtual-bank-accounts.show');
//     });

//     Route::prefix('wallets')->group(function () {
//         Route::get('/', [AdminWalletController::class, 'index'])->name('admin.wallets.index');
//         Route::get('/{id}', [AdminWalletController::class, 'show'])->name('admin.wallets.show');
//         Route::delete('/{id}', [AdminWalletController::class, 'destroy'])->name('admin.wallets.destroy');
//     });

//     Route::prefix('linked-bank-accounts')->group(function () {
//         Route::get('/', [AdminLinkedBankAccountController::class, 'index'])->name('admin.linked-bank-accounts.index');
//         Route::get('/{id}', [AdminLinkedBankAccountController::class, 'show'])->name('admin.linked-bank-accounts.show');
//         Route::get('/user/{userId}', [AdminLinkedBankAccountController::class, 'userLinkedBankAccounts'])->name('admin.linked-bank-accounts.user');
//     });

//     Route::prefix('beneficiaries')->group(function () {
//         Route::get('/', [AdminBeneficiaryController::class, 'index'])->name('admin.beneficiaries.index');
//         Route::get('/{id}', [AdminBeneficiaryController::class, 'show'])->name('admin.beneficiaries.show');
//         Route::get('/user/{userId}', [AdminBeneficiaryController::class, 'userBeneficiaries'])->name('admin.beneficiaries.user');
//     });

//     Route::prefix('settings')->group(function () {
//         Route::get('/', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
//         Route::post('/', [AdminSettingsController::class, 'store'])->name('admin.settings.store');
//         Route::get('/{id}', [AdminSettingsController::class, 'show'])->name('admin.settings.show');
//         Route::put('/{id}', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
//         Route::delete('/{id}', [AdminSettingsController::class, 'destroy'])->name('admin.settings.destroy');
//     });

//     Route::prefix('statistics')->group(function () {
//         Route::get('/', [AdminStatisticsController::class, 'index'])->name('admin.statistics.index');
//     });

//     Route::prefix('services')->group(function () {
//         Route::prefix('airtime')->group(function () {
//             Route::get('/stats', [AdminServiceManagementController::class, 'airtimeStatistics'])->name('admin.services.airtime.statistics');
//             Route::get('/networks', [AdminServiceManagementController::class, 'airtimeNetworks'])->name('admin.services.airtime.networks');
//             Route::get('/transactions', [AdminServiceManagementController::class, 'airtimeIndex'])->name('admin.services.airtime.index');
//             Route::get('/transactions/{id}', [AdminServiceManagementController::class, 'airtimeShow'])->name('admin.services.airtime.show');
//         });

//         Route::prefix('data')->group(function () {
//             Route::get('/stats', [AdminServiceManagementController::class, 'dataStatistics'])->name('admin.services.data.statistics');
//             Route::get('/transactions', [AdminServiceManagementController::class, 'dataIndex'])->name('admin.services.data.index');
//             Route::get('/transactions/{id}', [AdminServiceManagementController::class, 'dataShow'])->name('admin.services.data.show');
//             Route::get('/providers', [AdminServiceManagementController::class, 'dataProviders'])->name('admin.services.data.providers');
//         });

//         Route::prefix('electricity')->group(function () {
//             Route::get('/stats', [AdminServiceManagementController::class, 'electricityStatistics'])->name('admin.services.electricity.statistics');
//             Route::get('/providers', [AdminServiceManagementController::class, 'electricityProviders'])->name('admin.services.electricity.providers');
//             Route::get('/transactions', [AdminServiceManagementController::class, 'electricityIndex'])->name('admin.services.electricity.index');
//             Route::get('/transactions/{id}', [AdminServiceManagementController::class, 'electricityShow'])->name('admin.services.electricity.show');
//         });

//         Route::prefix('tv')->group(function () {
//             Route::get('/stats', [AdminServiceManagementController::class, 'tvStatistics'])->name('admin.services.tv.statistics');
//             Route::get('/providers', [AdminServiceManagementController::class, 'tvProviders'])->name('admin.services.tv.providers');
//             Route::get('/transactions', [AdminServiceManagementController::class, 'tvIndex'])->name('admin.services.tv.index');
//             Route::get('/transactions/{id}', [AdminServiceManagementController::class, 'tvShow'])->name('admin.services.tv.show');
//         });
//     });
// });


