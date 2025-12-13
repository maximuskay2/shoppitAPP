<?php

namespace App\Modules\Transaction\Notifications;

use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionRecord;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionRenewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $recordAmount;
    protected string $recordCurrency;
    protected string $planName;
    protected string $nextBillingDate;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public SubscriptionRecord $record,
        string $planName,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
        $this->recordAmount = $this->record->amount->getAmount()->toFloat();
        $this->recordCurrency = $this->record->currency;
        $this->planName = $planName;
        $this->nextBillingDate = Carbon::parse($this->record->ends_at)->format('M d, Y');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $pushNotification = $notifiable->push_in_app_notifications;

        $channels = ['mail', 'database'];

        if ($pushNotification) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Subscription Renewed')
            ->view(
                'email.user.subscription.subscription-renewed',
                [
                    'user' => $notifiable,
                    'subscription' => $this->subscription,
                    'record' => $this->record,
                    'plan' => $this->planName
                ]
            );
    }

    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'subscription_renewed';
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return CloudMessage
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Subscription Renewed',
                'body' => "Your {$this->planName} subscription has been renewed successfully.",
            ])
            ->withData([
                'subscription_id' => $this->subscription->id,
                'record_id' => $this->record->id,
                'amount' => $this->recordAmount,
                'currency' => $this->recordCurrency,
                'plan' => $this->planName,
                'type' => 'subscription_renewed',
            ]);
    }
    
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Subscription Renewed',
            'body' => "Your {$this->planName} subscription has been renewed successfully. Next billing date: {$this->nextBillingDate}.",
            'subscription_id' => $this->subscription->id,
            'record_id' => $this->record->id,
            'amount' => $this->recordAmount,
            'currency' => $this->recordCurrency,
            'plan' => $this->planName,
            'next_billing_date' => $this->nextBillingDate,
        ];
    }
}
