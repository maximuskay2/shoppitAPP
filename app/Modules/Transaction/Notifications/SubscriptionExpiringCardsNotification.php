<?php

namespace App\Modules\Transaction\Notifications;

use App\Modules\Transaction\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class SubscriptionExpiringCardsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription,
        public string $expiryDate,
        public string $cardBrand,
        public string $cardDescription,
        public string $nextPaymentDate,
        public string $planName
    ) {
        //
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
        return (new MailMessage)->subject('Payment Card Expiring Soon - Action Required')
            ->view(
                'email.user.subscription.subscription-expiring-cards',
                [
                    'user' => $notifiable,
                    'subscription' => $this->subscription,
                    'expiryDate' => $this->expiryDate,
                    'cardBrand' => $this->cardBrand,
                    'cardDescription' => $this->cardDescription,
                    'nextPaymentDate' => $this->nextPaymentDate,
                    'planName' => $this->planName
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
        return 'subscription-expiring-cards';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        
        $title = "Payment Card Expiring Soon";

        $body = "Your {$this->cardDescription} expires on {$this->expiryDate}. Please update your payment method to avoid service interruption for your {$this->planName} subscription.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'subscription-expiring-cards',
                'expiry_date' => $this->expiryDate,
                'card_brand' => $this->cardBrand,
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
            'title' => 'Payment Card Expiring Soon',
            'message' => "Your {$this->cardDescription} expires on {$this->expiryDate}. Please update your payment method to avoid service interruption for your {$this->planName} subscription.",
            'data' => [
                'user_id' => $notifiable->id,
                'subscription_id' => $this->subscription->id,
                'expiry_date' => $this->expiryDate,
                'card_brand' => $this->cardBrand,
                'card_description' => $this->cardDescription,
                'next_payment_date' => $this->nextPaymentDate,
                'plan_name' => $this->planName,
            ]
        ];
    }
}
