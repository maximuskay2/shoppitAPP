<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine plan type based on amount
        $type = $this->amount === 0 ? 'Free Plan' : 'Paid Plan';

        // Format price
        $price = [
            'amount' => $this->amount / 100, // Convert from kobo to naira
            'currency' => '₦',
            'billing' => $this->interval
        ];

        return [
            'level' => "Level {$this->key}",
            'name' => $this->name,
            'type' => $type,
            'price' => $price,
            'features' => $this->features ?? [],
            // Keep original fields for backward compatibility
            'id' => $this->id,
            'key' => $this->key,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'interval' => $this->interval,
            'paystack_plan_id' => $this->paystack_plan_id,
            'provider' => $this->provider,
        ];
    }
}