<?php

namespace App\Providers;

// use App\Events\User\Banking\ManualBankTransactionSyncEvent;

use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Events\OrderPaymentSuccessful;
use App\Modules\Commerce\Events\OrderProcessed;
use App\Modules\Commerce\Listeners\OrderCancelledListener;
use App\Modules\Commerce\Listeners\OrderCompletedListener;
use App\Modules\Commerce\Listeners\OrderDispatchedListener;
use App\Modules\Commerce\Listeners\OrderProcessedListener;
use App\Modules\Commerce\Listeners\OrderPaymentSuccessfulListener;
use App\Modules\Transaction\Events\FundWalletProccessed;
use App\Modules\Transaction\Events\FundWalletSuccessful;
use App\Modules\Transaction\Events\PaymentMethodInitializationSuccess;
use App\Modules\Transaction\Events\SubscriptionCancellation;
use App\Modules\Transaction\Events\SubscriptionChargeSuccess;
use App\Modules\Transaction\Events\SubscriptionCreationSuccess;
use App\Modules\Transaction\Events\SubscriptionExpiringCards;
use App\Modules\Transaction\Events\SubscriptionInvoiceCreated;
use App\Modules\Transaction\Events\SubscriptionInvoicePaymentFailed;
use App\Modules\Transaction\Events\SubscriptionInvoiceUpdated;
use App\Modules\Transaction\Events\WithdrawalFailed;
use App\Modules\Transaction\Events\WithdrawalProccessed;
use App\Modules\Transaction\Events\WithdrawalReversed;
use App\Modules\Transaction\Events\WithdrawalSuccessful;
use App\Modules\Transaction\Listeners\FundWalletProccessedListener;
use App\Modules\Transaction\Listeners\WithdrawalFailedListener;
use App\Modules\Transaction\Listeners\WithdrawalProccessedListener;
use App\Modules\Transaction\Listeners\WithdrawalReversedListener;
use App\Modules\Transaction\Listeners\WithdrawalSuccessfulListener;
use App\Modules\Transaction\Listeners\PaymentMethodInitializationSuccessListener;
use App\Modules\Transaction\Listeners\SubscriptionCancellationListener;
use App\Modules\Transaction\Listeners\SubscriptionChargeSuccessListener;
use App\Modules\Transaction\Listeners\SubscriptionCreationSuccessListener;
use App\Modules\Transaction\Listeners\SubscriptionExpiringCardsListener;
use App\Modules\Transaction\Listeners\SubscriptionInvoiceCreatedListener;
use App\Modules\Transaction\Listeners\SubscriptionInvoicePaymentFailedListener;
use App\Modules\Transaction\Listeners\SubscriptionInvoiceUpdatedListener;
use App\Modules\Transaction\Listeners\UpdateUserWalletWithTransactionListener;
use App\Modules\User\Events\UserCreatedEvent;
use App\Modules\User\Events\UserProfileUpdatedEvent;
use App\Modules\User\Listeners\CreateDefaultUserAvatarListener;
use App\Modules\User\Listeners\SendWelcomeOnboardNotificationListener;
use App\Listeners\NotificationMetricsListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        NotificationSent::class => [
            NotificationMetricsListener::class,
        ],
        NotificationFailed::class => [
            NotificationMetricsListener::class,
        ],
        UserCreatedEvent::class => [
            SendWelcomeOnboardNotificationListener::class,
            CreateDefaultUserAvatarListener::class,
        ],
        SubscriptionChargeSuccess::class => [
            SubscriptionChargeSuccessListener::class
        ],
        SubscriptionCreationSuccess::class => [
            SubscriptionCreationSuccessListener::class
        ],
        SubscriptionCancellation::class => [
            SubscriptionCancellationListener::class,
        ],
        SubscriptionExpiringCards::class => [
            SubscriptionExpiringCardsListener::class,
        ],
        SubscriptionInvoiceCreated::class => [
            SubscriptionInvoiceCreatedListener::class,
        ],
        SubscriptionInvoiceUpdated::class => [
            SubscriptionInvoiceUpdatedListener::class,
        ],
        SubscriptionInvoicePaymentFailed::class => [
            SubscriptionInvoicePaymentFailedListener::class,
        ],
        PaymentMethodInitializationSuccess::class => [
            PaymentMethodInitializationSuccessListener::class,
        ],
        FundWalletProccessed::class => [
            FundWalletProccessedListener::class,
        ],
        FundWalletSuccessful::class => [
            UpdateUserWalletWithTransactionListener::class
        ],
        OrderProcessed::class => [
            OrderProcessedListener::class,
        ],
        OrderPaymentSuccessful::class => [
            OrderPaymentSuccessfulListener::class,
        ],
        OrderDispatched::class => [
            OrderDispatchedListener::class,
        ],
        OrderCancelled::class => [
            OrderCancelledListener::class,
        ],
        OrderCompleted::class => [
            OrderCompletedListener::class,
        ],
        WithdrawalProccessed::class => [
            WithdrawalProccessedListener::class,
        ],
        WithdrawalSuccessful::class => [
            WithdrawalSuccessfulListener::class,
        ],
        WithdrawalFailed::class => [
            WithdrawalFailedListener::class,
        ],
        WithdrawalReversed::class => [
            WithdrawalReversedListener::class,
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
