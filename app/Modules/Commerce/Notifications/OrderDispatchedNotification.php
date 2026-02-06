<?php

namespace App\Modules\Commerce\Notifications;

use App\Modules\Commerce\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use NotificationChannels\FCM\FCMChannel;

class OrderDispatchedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $currentDateTime;
    protected float $grossTotal;
    protected float $couponDiscount;
    protected float $deliveryFee;
    protected float $netTotal;
    protected string $currency;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Order $order,
    ) {
        $this->currentDateTime = Carbon::now()->format('l, F j, Y \a\t g:i A');

        $this->grossTotal = $this->order->gross_total_amount->getAmount()->toFloat();
        $this->couponDiscount = $this->order->coupon_discount->getAmount()->toFloat();
        $this->deliveryFee = $this->order->delivery_fee->getAmount()->toFloat();
        $this->netTotal = $this->order->net_total_amount->getAmount()->toFloat();
        $this->currency = $this->order->currency;
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
        return (new MailMessage)->subject('Order Dispatched - On Its Way! 🚚')
            ->view(
                'email.user.order.order-dispatched',
                [
                    'user' => $notifiable,
                    'order' => $this->order,
                    'grossTotal' => $this->grossTotal,
                    'couponDiscount' => $this->couponDiscount,
                    'deliveryFee' => $this->deliveryFee,
                    'netTotal' => $this->netTotal,
                    'currency' => $this->currency,
                    'otp' => $this->order->otp_code,
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
        return 'order-dispatched';
    }


    /**
     * Get the in-app representation of the notification.
     */
    public function toFCM(object $notifiable): CloudMessage
    {
        $title = "Order Dispatched 🚚";

        $body = "Your order {$this->order->tracking_id} is on its way! Track your delivery now.";

        return CloudMessage::new()
            ->withDefaultSounds()
            ->withNotification([
                'title' => $title,
                'body' => $body,
            ])
            ->withData([
                'notification_key' => 'order-dispatched',
                'order_id' => $this->order->id,
                'tracking_id' => $this->order->tracking_id,
                'otp' => $this->order->otp_code ?? '',
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
            'amount' => $this->netTotal,
            'currency' => $this->currency,
            'status' => $this->order->status,
            'otp' => $this->order->otp_code,
        ];
    }
}
