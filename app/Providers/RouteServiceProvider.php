<?php

namespace App\Providers;

use App\Helpers\ShopittPlus;
use App\Models\User;
use App\Modules\User\Models\User as ModelsUser;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::prefix('api/v1/admin')
                ->group(base_path('routes/admin/v1/api.php'));
        });
    }


    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $correctCredentials = $this->checkUserCredentials($request->input('email'), $request->input('password'));
            return [];
            return $correctCredentials ?
                [
                    Limit::perMinute(5)->by($request->input('username'))->response(function (Request $request) {
                        return ShopittPlus::response(false, 'Rate limit exceeded. Please try again after 60 seconds!', 429);
                    }),
                ]
                : [
                    Limit::perMinute(5)->by($request->ip())->response(function (Request $request) {
                        return ShopittPlus::response(false, 'Rate limit exceeded. Please try again after 60 seconds!', 429);
                    }),
                ];
        });

        RateLimiter::for('otp', function (Request $request) {

            $shortTermLimit = Limit::perMinute(5)->by($request->ip() . '|minute')->response(function (Request $request) {
                return ShopittPlus::response(false, 'You have exceeded your limit. Please try again after 60 seconds!', 429);
            });

            $longTermLimit = Limit::perHour(10)->by($request->ip() . '|hour')->response(function (Request $request) {
                return ShopittPlus::response(false, 'You have exceeded your limit. Please try again after 60 minutes!', 429);
            });

            $longerTermLimit = Limit::perDay(30)->by($request->ip() . '|day')->response(function (Request $request) {
                return ShopittPlus::response(false, 'You have exceeded your limit. Please try again after 24 hours!', 429);
            });

            return [];

            return [
                $shortTermLimit,
                $longTermLimit,
                $longerTermLimit,
            ];
        });

        RateLimiter::for('location', function (Request $request) {
            return Limit::perMinute(12)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return ShopittPlus::response(false, 'Rate limit exceeded. Please try again later.', 429);
                });
        });

        RateLimiter::for('driver-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return ShopittPlus::response(false, 'Rate limit exceeded. Please try again later.', 429);
                });
        });

        RateLimiter::for('driver-status', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return ShopittPlus::response(false, 'Rate limit exceeded. Please try again later.', 429);
                });
        });

        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(20)->by($request->user('admin-api')?->id ?: $request->ip())
                ->response(function () {
                    return ShopittPlus::response(false, 'Rate limit exceeded. Please try again later.', 429);
                });
        });
    }

    /**
     * Checks the user credentials for correctness
     * @param string username
     * @param string password
     * 
     * @return bool | JsonResponse
     */
    private function checkUserCredentials($email, $password): bool | JsonResponse
    {
        try {
            $user = ModelsUser::where('email', $email)->first();
            if ($user && Hash::check($password, $user->password)) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            return ShopittPlus::response(false, 'Error encountered - ' . $e->getMessage(), 500);
        }
    }
}
