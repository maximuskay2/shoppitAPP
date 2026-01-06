<?php

use App\Http\Controllers\Api\V1\Admin\Account\AdminAccountController;
use App\Http\Controllers\Api\V1\Admin\AdminBlogController;
use App\Http\Controllers\Api\V1\Admin\AdminPromotionController;
use App\Http\Controllers\Api\V1\Admin\AdminRoleController;
use App\Http\Controllers\Api\V1\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\V1\Admin\AdminTransactionController;
use App\Http\Controllers\Api\V1\Admin\AdminSettingsController;
use App\Http\Controllers\Api\V1\Admin\Auth\AdminLoginController;
use App\Http\Controllers\Api\V1\Admin\Auth\AdminResetPasswordController;
use App\Http\Controllers\Api\V1\Admin\Otp\AdminOtpController;
use App\Http\Controllers\Api\V1\Admin\Notifications\AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\OrderManagementController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('admin.auth.')->group(function () {
    Route::middleware('throttle:login')->post('/login', AdminLoginController::class)->name('login');
    Route::middleware('throttle:otp')->post('/send-code', [AdminOtpController::class, 'send'])->name('send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [AdminOtpController::class, 'verify'])->name('verify.otp');
    Route::middleware('throttle:login')->post('/reset-password', AdminResetPasswordController::class)->name('reset.password');
});

Route::middleware(['auth:admin', 'admin'])->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [AdminAccountController::class, 'show'])->name('admin.show.account');
        Route::put('/', [AdminAccountController::class, 'update'])->name('admin.update.account');
        Route::post('/update-avatar', [AdminAccountController::class, 'updateAvatar'])->name('admin.update.avatar');
        Route::post('/change-password', [AdminAccountController::class, 'changePassword'])->name('admin.change.password');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [AdminNotificationController::class, 'index'])->name('admin.notifications.index');
        Route::get('/unread-count', [AdminNotificationController::class, 'unreadCount'])->name('admin.notifications.unread.count');
        Route::get('/{id}', [AdminNotificationController::class, 'show'])->name('admin.notifications.show');
        Route::post('/{id}/read', [AdminNotificationController::class, 'markRead'])->name('admin.notifications.read');
        Route::post('/mark-all-read', [AdminNotificationController::class, 'markAllRead'])->name('admin.notifications.read.all');
        Route::delete('/{id}', [AdminNotificationController::class, 'destroy'])->name('admin.notifications.delete');
        Route::delete('/', [AdminNotificationController::class, 'destroyAll'])->name('admin.notifications.delete.all');
    });

    Route::prefix('user-management')->middleware('user.management.scope')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::get('/stats', [UserManagementController::class, 'stats'])->name('admin.users.stats');
        Route::post('/', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::get('/{id}', [UserManagementController::class, 'show'])->name('admin.users.show');
        Route::put('/{id}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/{id}/suspend', [UserManagementController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/{id}/activate', [UserManagementController::class, 'activate'])->name('admin.users.activate');
        Route::post('/{id}/change-status', [UserManagementController::class, 'changeStatus'])->name('admin.users.change.status');
    });

    Route::prefix('order-management')->middleware('order.management.scope')->group(function () {
        Route::get('/', [OrderManagementController::class, 'index'])->name('admin.orders.index');
        Route::get('/{id}', [OrderManagementController::class, 'show'])->name('admin.orders.show');
        Route::get('/fetch/stats', [OrderManagementController::class, 'stats'])->name('admin.orders.stats');
        Route::post('/{id}/update-status', [OrderManagementController::class, 'updateStatus'])->name('admin.orders.update.status');
    });

    Route::prefix('transaction-management')->middleware('transaction.management.scope')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'index'])->name('admin.transactions.index');
        Route::get('/{id}', [AdminTransactionController::class, 'show'])->name('admin.transactions.show');
        Route::get('/fetch/stats', [AdminTransactionController::class, 'stats'])->name('admin.transactions.stats');
    });

    Route::prefix('subscription-management')->middleware('subscription.management.scope')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
        Route::get('/{id}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
    });

    Route::prefix('reports')->middleware('reports.management.scope')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'reports'])->name('admin.reports.index');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [AdminRoleController::class, 'index'])->name('admin.roles.index');
        Route::post('/', [AdminRoleController::class, 'store'])->name('admin.roles.store');
        Route::get('/{id}', [AdminRoleController::class, 'show'])->name('admin.roles.show');
        Route::put('/{id}', [AdminRoleController::class, 'update'])->name('admin.roles.update');
        Route::delete('/{id}', [AdminRoleController::class, 'destroy'])->name('admin.roles.destroy');
    });

    Route::prefix('settings')->group(function () {
        Route::get('/', [AdminSettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('/', [AdminSettingsController::class, 'store'])->name('admin.settings.store');
        Route::get('/{id}', [AdminSettingsController::class, 'show'])->name('admin.settings.show');
        Route::put('/{id}', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
        Route::delete('/{id}', [AdminSettingsController::class, 'destroy'])->name('admin.settings.destroy');
    });

    Route::prefix('blog-management')->middleware('blog.management.scope')->group(function () {
        Route::get('/stats', [AdminBlogController::class, 'stats'])->name('admin.blogs.stats');
        Route::get('/', [AdminBlogController::class, 'index'])->name('admin.blogs.index');
        Route::post('/', [AdminBlogController::class, 'store'])->name('admin.blogs.store');
        Route::get('/{id}', [AdminBlogController::class, 'show'])->name('admin.blogs.show');
        Route::post('/{id}', [AdminBlogController::class, 'update'])->name('admin.blogs.update');
        Route::delete('/{id}', [AdminBlogController::class, 'destroy'])->name('admin.blogs.destroy');

        Route::prefix('categories/management')->group(function () {
            Route::get('/', [AdminBlogController::class, 'indexCategories'])->name('admin.blog.categories.index');
            Route::post('/', [AdminBlogController::class, 'storeCategory'])->name('admin.blog.categories.store');
            Route::get('/{id}', [AdminBlogController::class, 'showCategory'])->name('admin.blog.categories.show');
            Route::put('/{id}', [AdminBlogController::class, 'updateCategory'])->name('admin.blog.categories.update');
            Route::delete('/{id}', [AdminBlogController::class, 'destroyCategory'])->name('admin.blog.categories.destroy');
        });
    });

    Route::prefix('promotion-management')->middleware('promotion.management.scope')->group(function () {
        Route::get('/stats', [AdminPromotionController::class, 'stats'])->name('admin.promotions.stats');
        Route::get('/', [AdminPromotionController::class, 'index'])->name('admin.promotions.index');
        Route::post('/', [AdminPromotionController::class, 'store'])->name('admin.promotions.store');
        Route::get('/{id}', [AdminPromotionController::class, 'show'])->name('admin.promotions.show');
        Route::post('/{id}', [AdminPromotionController::class, 'update'])->name('admin.promotions.update');
        Route::delete('/{id}', [AdminPromotionController::class, 'destroy'])->name('admin.promotions.destroy');
        Route::post('/{id}/approve', [AdminPromotionController::class, 'approve'])->name('admin.promotions.approve');
        Route::post('/{id}/reject', [AdminPromotionController::class, 'reject'])->name('admin.promotions.reject');
    });
});