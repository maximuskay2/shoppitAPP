<?php

namespace App\Providers;

use App\Console\Commands\TestSmtpOtpCommand;
use App\Helpers\RuntimeConfig;
use App\Modules\Transaction\Console\Commands\FailPendingOrders;
use App\Modules\Transaction\Console\Commands\FailPendingWalletFundingTransactions;
use App\Modules\Transaction\Console\Commands\SubscriptionExpiredCommand;
use App\Modules\Transaction\Console\Commands\SubscriptionRemindersCommand;
use App\Modules\Transaction\Console\Commands\SubscriptionRevertCommand;
use App\Modules\Transaction\Console\Commands\SubscriptionRevertReminderCommand;
use App\Modules\User\Commands\ActivateExistingUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {  
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Model::shouldBeStrict();

        RuntimeConfig::applySmtpConfig();
        RuntimeConfig::applyCloudinaryConfig();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TestSmtpOtpCommand::class,
                ActivateExistingUsers::class,
                FailPendingOrders::class,
                FailPendingWalletFundingTransactions::class,
                SubscriptionExpiredCommand::class,
                SubscriptionRemindersCommand::class,
                SubscriptionRevertCommand::class,
                SubscriptionRevertReminderCommand::class,
            ]);
        }
    }
}
