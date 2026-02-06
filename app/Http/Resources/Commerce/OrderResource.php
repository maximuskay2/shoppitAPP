<?php

namespace App\Http\Resources\Commerce;

use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\VendorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'vendor_id' => $this->vendor_id,
            'driver_id' => $this->driver_id,
            'status' => $this->status,
            'email' => $this->email,
            'tracking_id' => $this->tracking_id,
            'order_notes' => $this->order_notes,
            'is_gift' => $this->is_gift,
            'receiver_delivery_address' => $this->receiver_delivery_address,
            'receiver_name' => $this->receiver_name,
            'receiver_email' => $this->receiver_email,
            'receiver_phone' => $this->receiver_phone,
            'currency' => $this->currency,
            'payment_reference' => $this->payment_reference,
            'processor_transaction_id' => $this->processor_transaction_id,
            'delivery_fee' => $this->delivery_fee->getAmount()->toFloat(),
            'gross_total_amount' => $this->gross_total_amount->getAmount()->toFloat(),
            'net_total_amount' => $this->net_total_amount->getAmount()->toFloat(),
            'paid_at' => $this->paid_at,
            'dispatched_at' => $this->dispatched_at,
            'assigned_at' => $this->assigned_at,
            'picked_up_at' => $this->picked_up_at,
            'delivered_at' => $this->delivered_at,
            'completed_at' => $this->completed_at,
            'settled_at' => $this->settled_at,
            'coupon_code' => $this->coupon_code,
            'coupon_discount' => $this->coupon_discount->getAmount()->toFloat(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'line_items' => OrderLineItemResource::collection($this->whenLoaded('lineItems')),
            'vendor' => new VendorResource($this->whenLoaded('vendor.user')),
            'user' => new UserResource($this->whenLoaded('user')),
            'coupon' => $this->whenLoaded('coupon'),
        ];
    }
}