<?php
namespace App\Modules\User\Notifications;

use App\Modules\User\Models\KycDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class KycDocumentSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public KycDocument $kycDocument
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
        return (new MailMessage)->subject('KYC Document Uploaded Successfully 📄')
            ->markdown(
                'email.user.kyc.kyc-document-submitted',
                [
                    'user' => $notifiable,
                    'document' => $this->kycDocument
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
        return 'kyc-document-submitted';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "KYC Document Uploaded 📄";
        $body = "Your {$this->kycDocument->document_type_display} has been uploaded successfully and is under review.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'kyc-document-submitted',
                'kyc_document_id' => $this->kycDocument->id,
                'document_type' => $this->kycDocument->document_type,
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
            'title' => 'KYC Document Uploaded 📄',
            'message' => "Your {$this->kycDocument->document_type_display} has been uploaded successfully and is under review.",
            'data' => [
                'kyc_document_id' => $this->kycDocument->id,
                'document_type' => $this->kycDocument->document_type,
                'document_type_display' => $this->kycDocument->document_type_display,
                'uploaded_at' => $this->kycDocument->created_at,
                'status' => $this->kycDocument->status->value,
            ]
        ];
    }
}