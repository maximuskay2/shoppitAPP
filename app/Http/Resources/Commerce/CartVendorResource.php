<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartVendorResource extends JsonResource
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
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'subtotal' => $this->subtotal(),
            'coupon' => $this->when($this->coupon_id, [
                'id' => $this->coupon_id,
                'code' => $this->coupon_code,
                'discount' => $this->discountAmount(),
            ]) ?? null,
            'delivery_fee' => $this->whenLoaded('vendor')->delivery_fee->getAmount()->toFloat(),
            'vendor_total' => $this->total(),
            'item_count' => $this->items->count(),
        ];
    }
}
