<?php

namespace App\Modules\Transaction\Notifications;

use App\Models\Transaction;
use App\Models\User\Wallet;
use App\Modules\Transaction\Models\SubscriptionPlan;
use App\Modules\Transaction\Models\SubscriptionRecord;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionPaymentNotification
 extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    public $status;
    protected float $recordAmount;
    protected string $recordCurrency;
    protected string $planName;
    protected string $ends_at;
    protected bool $renewal;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SubscriptionRecord $record,
        public SubscriptionPlan $plan,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
        $this->recordAmount = $this->plan->amount->getAmount()->toFloat();
        $this->recordCurrency = $this->record->currency;
        $this->status = $this->record->status->value;
        $this->planName = $this->plan->name;
        $this->ends_at = $this->record->ends_at;
        $this->renewal = $this->record->payload['renewal'];
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
    public function toMail(object $notifiable): MailMessage|null
    {
        if ($this->status == "PENDING") {
            return null;
        } else if ($this->status == "SUCCESSFUL")  {
            if (!$this->renewal) {
                return (new MailMessage)->subject('Subscription Successful')
                    ->view(
                        'email.user.subscription.subscription-successful',
                        ['user' => $notifiable, 'record' => $this->record, 'plan' => $this->planName]
                    );
            } else {
                return (new MailMessage)->subject('Subscription Renewed')
                    ->view(
                        'email.user.subscription.subscription-renewed',
                        ['user' => $notifiable, 'record' => $this->record, 'plan' => $this->planName]
                    );
            }
        } else {
            return (new MailMessage)->subject('Subscription Reversed')
                ->view(
                    'email.user.subscription.subscription-reversed',
                    ['user' => $notifiable, 'record' => $this->record, 'plan' => $this->planName]
                );
        }
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        if ($this->status == "PENDING") {
            return  'subscription-processing';
        } else if ($this->status == "SUCCESSFUL") {
            if (!$this->renewal) {
                return 'subscription-successful';
            } else {
                return 'subscription-renewed';
            }
        } else {
            return 'subscription-reversed';
        }
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        if ($this->status == "PENDING") {
            $title = "Subscription Processing";

            $body = "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency is processing.";
            
            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'subscription-processing',
                ]);
        } else if ($this->status == "SUCCESSFUL") {
            if (!$this->renewal) {
                $title = "Subscription Successful";

                $body = "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency is successful.";

                return CloudMessage::new()
                    ->withDefaultSounds()
                    ->withNotification([
                        'title' => $title,
                        'body' => $body,
                    ])
                    ->withData([
                        'notification_key' => 'subscription-successful',
                    ]);
                } else {
                    $title = "Subscription Renewed";
    
                    $body = "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency has been renewed.";
    
                    return CloudMessage::new()
                        ->withDefaultSounds()
                        ->withNotification([
                            'title' => $title,
                            'body' => $body,
                        ])
                        ->withData([
                            'notification_key' => 'subscription-renewed',
                        ]);
                }
        } else {
            $title = "Subscription Reversed";

            $body = "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency failed and your funds reversed.";

            return CloudMessage::new()
                ->withDefaultSounds()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData([
                    'notification_key' => 'subscription-reversed',
                ]);
        }
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        if ($this->status == "PENDING") {
            return [
                'title' => 'Subscription Processing',
                'message' => "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency is processing",
                'data' => [
                    'user_id' => $notifiable->id,
                    'record' => $this->record,
                    'plan' => $this->plan,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        } else if ($this->status == "SUCCESSFUL") {
            if (!$this->renewal) {
                return [
                    'title' => 'Subscription Successful',
                    'message' => "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency is successful.",
                    'data' => [
                        'user_id' => $notifiable->id,
                        'record' => $this->record,
                        'plan' => $this->plan,
                        'event_at' => $this->currentDateTime,
                    ]
                ];
            } else {
                return [
                    'title' => 'Subscription Renewed',
                    'message' => "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency has been renewed.",
                    'data' => [
                        'user_id' => $notifiable->id,
                        'record' => $this->record,
                        'plan' => $this->plan,
                        'event_at' => $this->currentDateTime,
                    ]
                ];
            }
        } else {
            return [
                'title' => 'Subscription Reversed',
                'message' => "Your $this->planName plan subscription of $this->recordAmount $this->recordCurrency failed and your funds reversed",
                'data' => [
                    'user_id' => $notifiable->id,
                    'record' => $this->record,
                    'plan' => $this->plan,
                    'event_at' => $this->currentDateTime,
                ]
            ];
        }
    }
}
