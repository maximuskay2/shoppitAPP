<?php

namespace App\Modules\User\Notifications;

use App\Modules\User\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class KycVerificationSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public KycVerification $kycVerification
    ) {}

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
        return (new MailMessage)->subject('KYC Verification Submitted Successfully ✅')
            ->markdown(
                'email.user.kyc.kyc-verification-submitted',
                [
                    'user' => $notifiable,
                    'verification' => $this->kycVerification
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
        return 'kyc-verification-submitted';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "KYC Verification Submitted ✅";
        $body = "Your Level {$this->kycVerification->level->value} KYC verification has been submitted and is under review.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'kyc-verification-submitted',
                'kyc_verification_id' => $this->kycVerification->id,
                'kyc_level' => $this->kycVerification->level->value,
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
            'title' => 'KYC Verification Submitted ✅',
            'message' => "Your Level {$this->kycVerification->level->value} KYC verification has been submitted and is under review. We'll notify you once it's processed.",
            'data' => [
                'kyc_verification_id' => $this->kycVerification->id,
                'kyc_level' => $this->kycVerification->level->value,
                'submitted_at' => $this->kycVerification->submitted_at,
                'status' => $this->kycVerification->status->value,
            ]
        ];
    }
}