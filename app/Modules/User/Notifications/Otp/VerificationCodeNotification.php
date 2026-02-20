<?php

namespace App\Modules\User\Notifications\Otp;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * OTP emails are sent synchronously so they arrive immediately.
 * ShouldQueue was removed because queued jobs require a running worker;
 * without one, OTP emails never got sent when initiated from the app.
 */
class VerificationCodeNotification extends Notification
{

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $verification_code,
        protected int $expiry_minutes,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        $channels = ['mail'];
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Verification Code 🔔')
            ->markdown(
                'email.user.otp.verification-code',
                ['verification_code' => $this->verification_code, 'expiry_minutes' => $this->expiry_minutes]
            );
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [];
    }
}
