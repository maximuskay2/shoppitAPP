<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'provider' => $this->provider,
            'method' => $this->method,
            'card_type' => $this->card_type,
            'last_four' => $this->last_four,
            'expiry_month' => $this->expiry_month,
            'expiry_year' => $this->expiry_year,
            'account_name' => $this->account_name,
            'bank' => $this->bank,
            'brand' => $this->brand,
            'currency' => $this->currency,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ];
    }
}