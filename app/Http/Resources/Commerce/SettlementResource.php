<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettlementResource extends JsonResource
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
            'total_amount' => $this->total_amount->getAmount()->toFloat(),
            'platform_fee' => $this->platform_fee->getAmount()->toFloat(),
            'vendor_amount' => $this->vendor_amount->getAmount()->toFloat(),
            'payment_gateway' => $this->payment_gateway,
            'status' => $this->status,
            'settled_at' => $this->settled_at,
            'currency' => $this->currency,
        ];
    }
}