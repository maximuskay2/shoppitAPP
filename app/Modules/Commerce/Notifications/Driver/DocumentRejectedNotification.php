<?php

namespace App\Modules\Commerce\Notifications\Driver;

use App\Modules\User\Models\DriverDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class DocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public DriverDocument $document)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->push_in_app_notifications ?? true) {
            $channels[] = FCMChannel::class;
        }

        return $channels;
    }

    public function databaseType(object $notifiable): string
    {
        return 'document.rejected';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $documentType = $this->formatDocumentType($this->document->document_type);
        $reason = $this->document->rejection_reason ?? 'Please ensure the document is clear and readable.';

        return (new MailMessage)
            ->subject('Document Rejected – Action Required')
            ->markdown('email.driver.document-rejected', [
                'user' => $notifiable,
                'document' => $this->document,
                'documentType' => $documentType,
                'reason' => $reason,
            ]);
    }

    public function toFCM(object $notifiable): CloudMessage
    {
        $documentType = $this->formatDocumentType($this->document->document_type);
        $body = "Your {$documentType} was rejected. Please upload a new document in the app.";

        if ($this->document->rejection_reason) {
            $body = "Your {$documentType} was rejected: {$this->document->rejection_reason}. Please upload a new document.";
        }

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => 'Document Rejected',
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'document.rejected',
                'document_id' => $this->document->id,
                'document_type' => $this->document->document_type,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        $documentType = $this->formatDocumentType($this->document->document_type);

        return [
            'title' => 'Document Rejected',
            'message' => "Your {$documentType} was rejected. Please upload a new document in the Documents section.",
            'data' => [
                'document_id' => $this->document->id,
                'document_type' => $this->document->document_type,
                'rejection_reason' => $this->document->rejection_reason,
                'rejected_at' => $this->document->rejected_at?->toIso8601String(),
            ],
        ];
    }

    private function formatDocumentType(string $type): string
    {
        return match ($type) {
            'drivers_license' => 'driver license',
            'vehicle_registration' => 'vehicle registration',
            'insurance' => 'insurance',
            'government_id' => 'government ID',
            default => str_replace('_', ' ', $type),
        };
    }
}
