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

class SubscriptionReminderNotification

 extends Notification implements ShouldQueue
{
    use Queueable;

    public $plan;
    public $end;
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public SubscriptionPlan $subscriptionPlan,
    ) {
        $this->plan = ucfirst($subscriptionPlan->name);
        $this->end = $this->subscription->ends_at->diffForHumans();
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
        return (new MailMessage)->subject('Subscription Expiration Reminder')
            ->view(
                'email.user.subscription.subscription-reminder',
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
        return 'subscription-reminder';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Subscription Expiration Reminder";

        $body = "Your $this->plan subscription plan wll expire $this->end. You can either set up auto renewal or be ready to manually renew when it expires.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-reminder',
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
            'title' => 'Subscription Expiration Reminder',
            'message' => "Your $this->plan subscription plan wll expire $this->end. You can either set up auto renewal or be ready to manually renew when it expires",
            'data' => [
                'user_id' => $notifiable->id,
                'subscriptionPlan' => $this->subscriptionPlan,
                'subscription' => $this->subscription,
            ]
        ];
    }
}
