<?php

namespace App\Modules\Commerce\Notifications;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settlement;
use App\Modules\Transaction\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderCompletedVendorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $grossTotal;
    protected float $couponDiscount;
    protected float $deliveryFee;
    protected float $netTotal;
    protected string $currency;
    protected float $totalAmount;
    protected float $platformFee;
    protected float $vendorAmount;
    protected float $walletBalance;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
        public Settlement $settlement,
        public Wallet $wallet,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->grossTotal = $this->order->gross_total_amount->getAmount()->toFloat();
        $this->couponDiscount = $this->order->coupon_discount->getAmount()->toFloat();
        $this->deliveryFee = $this->order->delivery_fee->getAmount()->toFloat();
        $this->netTotal = $this->order->net_total_amount->getAmount()->toFloat();
        $this->currency = $this->order->currency;

        // Settlement details
        $this->totalAmount = $this->settlement->total_amount->getAmount()->toFloat();
        $this->platformFee = $this->settlement->platform_fee->getAmount()->toFloat();
        $this->vendorAmount = $this->settlement->vendor_amount->getAmount()->toFloat();
        
        // Wallet balance
        $this->walletBalance = $this->wallet->balance;
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
        return (new MailMessage)->subject('Order Completed & Payment Settled 💰')
            ->view(
                'email.vendor.order.order-completed-vendor',
                [
                    'vendor' => $notifiable,
                    'order' => $this->order,
                    'settlement' => $this->settlement,
                    'wallet' => $this->wallet,
                    'grossTotal' => $this->grossTotal,
                    'couponDiscount' => $this->couponDiscount,
                    'deliveryFee' => $this->deliveryFee,
                    'netTotal' => $this->netTotal,
                    'currency' => $this->currency,
                    'totalAmount' => $this->totalAmount,
                    'platformFee' => $this->platformFee,
                    'vendorAmount' => $this->vendorAmount,
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
        return 'order-completed-vendor';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Order Completed & Payment Settled 💰";

        $body = "Order {$this->order->tracking_id} completed! {$this->currency} " . number_format($this->vendorAmount, 2) . " has been credited to your wallet.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'order-completed-vendor',
                'order_id' => $this->order->id,
                'tracking_id' => $this->order->tracking_id,
                'settlement_id' => $this->settlement->id,
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
            'settlement_id' => $this->settlement->id,
            'vendor_amount' => $this->vendorAmount,
            'platform_fee' => $this->platformFee,
            'currency' => $this->currency,
            'wallet_balance' => $this->walletBalance,
        ];
    }
}
