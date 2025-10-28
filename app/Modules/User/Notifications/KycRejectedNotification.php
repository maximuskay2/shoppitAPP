<?php
namespace App\Modules\User\Notifications;

use App\Modules\User\Models\KycVerification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class KycRejectedNotification extends Notification implements ShouldQueue
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
        return (new MailMessage)->subject('KYC Verification Update Required ⚠️')
            ->markdown(
                'email.user.kyc.kyc-rejected',
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
        return 'kyc-rejected';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "KYC Verification Update Required ⚠️";
        $body = "Your KYC verification needs attention. Please review the feedback and resubmit your documents.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'kyc-rejected',
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
            'title' => 'KYC Verification Update Required ⚠️',
            'message' => "Your KYC verification couldn't be approved. Please review the feedback and submit updated documents to continue.",
            'data' => [
                'kyc_verification_id' => $this->kycVerification->id,
                'kyc_level' => $this->kycVerification->level->value,
                'rejected_at' => $this->kycVerification->reviewed_at,
                'rejection_reason' => $this->kycVerification->rejection_reason,
                'admin_notes' => $this->kycVerification->admin_notes,
                'status' => $this->kycVerification->status->value,
            ]
        ];
    }
}