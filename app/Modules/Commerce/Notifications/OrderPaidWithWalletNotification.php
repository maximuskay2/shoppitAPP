<?php

namespace App\Modules\Commerce\Notifications;

use App\Modules\Commerce\Models\Order;
use App\Modules\Transaction\Models\Transaction;
use App\Modules\Transaction\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderPaidWithWalletNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $grossTotal;
    protected float $couponDiscount;
    protected float $deliveryFee;
    protected float $netTotal;
    protected string $currency;
    protected float $transactionAmount;
    protected float $walletBalance;
    protected float $fees;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public Transaction $transaction,
        public Wallet $wallet,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->grossTotal = $this->order->gross_total_amount->getAmount()->toFloat();
        $this->couponDiscount = $this->order->coupon_discount->getAmount()->toFloat();
        $this->deliveryFee = $this->order->delivery_fee->getAmount()->toFloat();
        $this->netTotal = $this->order->net_total_amount->getAmount()->toFloat();
        $this->currency = $this->order->currency;

        $this->transactionAmount = $this->transaction->amount->getAmount()->toFloat();
        $this->walletBalance = $this->wallet->amount->getAmount()->toFloat();
        $this->fees = $this->transaction->feeTransactions()->first()?->amount->getAmount()->toFloat() ?? 0.0;
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
        return (new MailMessage)->subject('Order Paid Successfully 🛍️💳')
            ->view(
                'email.user.order.order-paid-with-wallet',
                [
                    'user' => $notifiable,
                    'order' => $this->order,
                    'transaction' => $this->transaction,
                    'wallet' => $this->wallet,
                    'grossTotal' => $this->grossTotal,
                    'couponDiscount' => $this->couponDiscount,
                    'deliveryFee' => $this->deliveryFee,
                    'netTotal' => $this->netTotal,
                    'currency' => $this->currency,
                    'transactionAmount' => $this->transactionAmount,
                    'walletBalance' => $this->walletBalance,
                    'feeAmount' => $this->fees,
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
        return 'order-paid-with-wallet';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Order Paid Successfully 🛍️💳";

        $body = "Your order {$this->order->tracking_id} has been paid from your wallet. Amount: {$this->currency} " . number_format($this->transactionAmount, 2);

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'order-paid-with-wallet',
                'order_id' => $this->order->id,
                'tracking_id' => $this->order->tracking_id,
                'transaction_id' => $this->transaction->id,
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
            'order_id' => $this->order->id,
            'tracking_id' => $this->order->tracking_id,
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transactionAmount,
            'currency' => $this->currency,
            'wallet_balance' => $this->walletBalance,
        ];
    }
}
