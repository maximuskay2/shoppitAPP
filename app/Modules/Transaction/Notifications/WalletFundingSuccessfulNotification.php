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

class WalletFundingSuccessfulNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $transactionAmount;
    protected string $transactionCurrency;
    protected float $walletAmount;
    protected float $fees;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Transaction $transaction,
        public Wallet $wallet,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->transactionCurrency = $this->transaction->currency;
        $this->walletAmount = $this->wallet->amount->getAmount()->toFloat();
        $this->fees = $this->transaction->feeTransactions()->first()->amount->getAmount()->toFloat();
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
        return (new MailMessage)->subject('Wallet Funding Successful 💸')
            ->view(
                'email.user.wallet.funding-successful',
                ['user' => $notifiable, 'transactionAmount' => $this->transactionAmount , 'transactionCurrency' => $this->transactionCurrency, 'transaction' => $this->transaction, 'walletAmount' => $this->walletAmount, 'feeAmount' => $this->fees]
            );
    }


    /**
     * Get the notification's database type.
     *
     * @return string
     */
    public function databaseType(object $notifiable): string
    {
        return 'fund-wallet-successful';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Wallet Funding Successful 💸";

        $body = "Funding of $this->transactionCurrency $this->transactionAmount successful 💸.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'fund-wallet-successful',
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
            'title' => 'Wallet Funding Successful 💸',
            'message' => "Funding of $this->transactionCurrency $this->transactionAmount successful 💸.",
            'data' => [
                'user_id' => $notifiable->id,
                'transaction' => $this->transaction,
                'wallet' => $this->wallet,
                'event_at' => $this->currentDateTime,
            ]
        ];
    }
}
