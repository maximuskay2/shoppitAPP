<?php

namespace App\Modules\Transaction\Notifications;

use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class WithdrawalReversedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $amount;
    protected float $feeAmount;
    protected string $currency;
    protected float $walletBalance;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public Wallet $wallet,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->amount = $this->transaction->amount->getAmount()->toFloat();
        $this->feeAmount = $this->transaction->feeTransactions->sum(function ($fee) {
            return $fee->amount->getAmount()->toFloat();
        });
        $this->currency = $this->transaction->currency;
        $this->walletBalance = $this->wallet->balance->getAmount()->toFloat();
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
        return (new MailMessage)->subject('Withdrawal Reversed - Funds Returned 🔄')
            ->view(
                'email.user.wallet.withdrawal-reversed',
                [
                    'user' => $notifiable,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'amount' => $this->amount,
                    'feeAmount' => $this->feeAmount,
                    'currency' => $this->currency,
                    'walletBalance' => $this->walletBalance,
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
        return 'withdrawal-reversed';
    }

    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Withdrawal Reversed 🔄";

        $body = "Your withdrawal of {$this->currency} " . number_format($this->amount, 2) . " has been reversed. Funds returned to your wallet.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'withdrawal-reversed',
                'transaction_id' => $this->transaction->id,
                'reference' => $this->transaction->reference,
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
            'transaction_id' => $this->transaction->id,
            'reference' => $this->transaction->reference,
            'amount' => $this->amount,
            'fee' => $this->feeAmount,
            'currency' => $this->currency,
            'wallet_balance' => $this->walletBalance,
        ];
    }
}
