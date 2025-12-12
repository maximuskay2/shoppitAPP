<?php

namespace App\Providers;

// use App\Events\User\Banking\ManualBankTransactionSyncEvent;

use App\Modules\Commerce\Listeners\HandlePaystackChargeSuccess;
use App\Modules\Transaction\Events\PaystackChargeSuccessEvent;
use App\Modules\Transaction\Events\SubscriptionChargeSuccess;
use App\Modules\Transaction\Events\SubscriptionCreationSuccess;
use App\Modules\Transaction\Listeners\SubscriptionChargeSuccessListener;
use App\Modules\Transaction\Listeners\SubscriptionCreationSuccessListener;
use App\Modules\User\Events\UserCreatedEvent;
use App\Modules\User\Events\UserProfileUpdatedEvent;
use App\Modules\User\Listeners\CreateDefaultUserAvatarListener;
use App\Modules\User\Listeners\SendWelcomeOnboardNotificationListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        UserCreatedEvent::class => [
            SendWelcomeOnboardNotificationListener::class,
            CreateDefaultUserAvatarListener::class,
        ],
        PaystackChargeSuccessEvent::class => [
            HandlePaystackChargeSuccess::class,
        ],
        SubscriptionChargeSuccess::class => [
            SubscriptionChargeSuccessListener::class
        ],
        SubscriptionCreationSuccess::class => [
            SubscriptionCreationSuccessListener::class
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
