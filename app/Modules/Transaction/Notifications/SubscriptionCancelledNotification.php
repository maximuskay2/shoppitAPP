<?php

namespace App\Modules\Transaction\Notifications;

use App\Modules\Transaction\Models\Subscription;
use App\Modules\Transaction\Models\SubscriptionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $plan;
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public SubscriptionPlan $subscriptionPlan,
        public Subscription $subscription,
    ) {
        $this->plan = ucfirst($subscriptionPlan->name);
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
        return (new MailMessage)->subject('Subscription Cancelled')
            ->view(
                'email.user.subscription.subscription-cancelled',
                ['user' => $notifiable, 'plan' => $this->subscriptionPlan, 'subscription' => $this->subscription]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'subscription-cancelled';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Subscription Cancelled";

        $body = "Your $this->plan subscription plan has been cancelled. You will no longer have access to premium features after the current billing period.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-cancelled',
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
            'title' => 'Subscription Cancelled',
            'message' => "Your $this->plan subscription plan has been cancelled. You will no longer have access to premium features after the current billing period.",
            'data' => [
                'user_id' => $notifiable->id,
                'subscription_plan' => $this->subscriptionPlan,
            ]
        ];
    }
}
