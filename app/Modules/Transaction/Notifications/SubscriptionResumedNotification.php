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

class SubscriptionResumedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)->subject('Subscription Resumed')
            ->view(
                'email.user.subscription.subscription-resumed',
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
        return 'subscription-resumed';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Subscription Resumed";

        $body = "Welcome back! Your $this->plan subscription plan has been resumed. You now have full access to all premium features.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-resumed',
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
            'title' => 'Subscription Resumed',
            'message' => "Welcome back! Your $this->plan subscription plan has been resumed. You now have full access to all premium features.",
            'data' => [
                'user_id' => $notifiable->id,
                'subscription_plan' => $this->subscriptionPlan,
            ]
        ];
    }
}
