<?php

namespace App\Modules\User\Notifications;

use App\Modules\User\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class KycVerificationReceivedNotification extends Notification implements ShouldQueue
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
        $pushNotification = $notifiable->push_in_app_notifications ?? true;

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
        return (new MailMessage)->subject('New KYC Verification Submitted 📋')
            ->markdown(
                'email.admin.kyc.kyc-verification-received',
                [
                    'admin' => $notifiable,
                    'verification' => $this->kycVerification,
                    'user' => $this->kycVerification->user
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
        return 'kyc-verification-received';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $user = $this->kycVerification->user;
        $title = "New KYC Verification 📋";
        $body = "{$user->name} has submitted a Level {$this->kycVerification->level->value} KYC verification for review.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'kyc-verification-received',
                'kyc_verification_id' => $this->kycVerification->id,
                'user_id' => $user->id,
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
        $user = $this->kycVerification->user;
        
        return [
            'title' => 'New KYC Verification Submitted 📋',
            'message' => "{$user->name} has submitted a Level {$this->kycVerification->level->value} KYC verification. Please review the application.",
            'data' => [
                'kyc_verification_id' => $this->kycVerification->id,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'kyc_level' => $this->kycVerification->level->value,
                'submitted_at' => $this->kycVerification->submitted_at,
                'status' => $this->kycVerification->status->value,
            ]
        ];
    }
}