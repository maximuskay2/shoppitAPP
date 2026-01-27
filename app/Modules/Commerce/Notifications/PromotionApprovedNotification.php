<?php

namespace App\Modules\Commerce\Notifications;

use App\Modules\Commerce\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class PromotionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Promotion $promotion,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');
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
        return (new MailMessage)->subject('Your Promotion Request Has Been Approved! 🎉')
            ->view(
                'email.vendor.promotion.promotion-approved',
                [
                    'vendor' => $notifiable,
                    'promotion' => $this->promotion,
                    'currentDateTime' => $this->currentDateTime,
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
        return 'promotion-approved';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Promotion Approved! 🎉";

        $body = "Your promotion '{$this->promotion->title}' has been approved and is now active!";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'promotion-approved',
                'promotion_id' => $this->promotion->id,
                'promotion_title' => $this->promotion->title,
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
            'promotion_id' => $this->promotion->id,
            'promotion_title' => $this->promotion->title,
            'discount_type' => $this->promotion->discount_type,
            'discount_value' => $this->promotion->discount_value,
        ];
    }
}
