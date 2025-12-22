<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subtotal = $this->vendors->sum(fn($vendor) => $vendor->subtotal());
        $totalDiscount = $this->vendors->sum(fn($vendor) => $vendor->discountAmount());
        
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'vendors' => CartVendorResource::collection($this->whenLoaded('vendors')),
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
            'cart_total' => $this->total(),
            'total_items' => $this->items->count(),
            'vendor_count' => $this->vendors->count(),
        ];
    }
}