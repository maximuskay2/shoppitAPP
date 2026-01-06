<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders()
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register your middleware here (previously in Http/Kernel.php)
        $middleware->use([
            // \App\Http\Middleware\TrustHosts::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \App\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJson::class,
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'user.is.email.verified' => \App\Http\Middleware\UserIsEmailVerified::class,
            'user.is.active' => \App\Http\Middleware\UserIsActive::class,
            'user.is.vendor' => \App\Http\Middleware\UserIsVendor::class,
            'user.is.not.vendor' => \App\Http\Middleware\UserIsNotVendor::class,
            'vendor.subscription.product.listing' => \App\Http\Middleware\Subscription\ProductListing::class,
            'admin' => \App\Http\Middleware\UserIsAdmin::class,
            'admin.is.super.admin' => \App\Http\Middleware\UserIsSuperAdmin::class,
            'user.management.scope' => \App\Http\Middleware\CheckUserManagementScope::class,
            'order.management.scope' => \App\Http\Middleware\CheckOrderManagementScope::class,
            'transaction.management.scope' => \App\Http\Middleware\CheckTransactionManagementScope::class,
            'subscription.management.scope' => \App\Http\Middleware\CheckSubscriptionManagementScope::class,
            'reports.management.scope' => \App\Http\Middleware\CheckReportsManagementScope::class,
            'blog.management.scope' => \App\Http\Middleware\CheckBlogManagementScope::class,
            'promotion.management.scope' => \App\Http\Middleware\CheckPromotionManagementScope::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule) {
        app(App\Modules\Transaction\Console\Kernel::class)->schedule($schedule);
    })
    ->create();
