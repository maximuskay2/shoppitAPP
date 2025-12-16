<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
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
            'code' => $this->code,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount->getAmount()->toFloat(),
            'percent' => $this->percent,
            'minimum_order_value' => $this->minimum_order_value->getAmount()->toFloat(),
            'maximum_discount' => $this->maximum_discount->getAmount()->toFloat(),
            'usage_per_customer' => $this->usage_per_customer,
            'usage_count' => $this->usage_count,
            'is_visible' => $this->is_visible,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'vendor' => $this->whenLoaded('vendor'),
        ];
    }
}
