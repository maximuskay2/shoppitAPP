<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
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
            'name' => $this->user->name,
            'business_name' => $this->business_name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'avatar' => $this->user->avatar,
            'type' => 'vendor',
            'email_verified_at' => $this->user->email_verified_at,
            'kyb_status' => $this->kyb_status,
            'username' => $this->user->username,
            'address' => $this->user->address,
            'address_2' => $this->user->address_2,
            'city' => $this->user->city,
            'state' => $this->user->state,
            'country' => $this->user->country,
            'opening_time' => $this->opening_time?->format('g:i A'),
            'closing_time' => $this->closing_time?->format('g:i A'),
            'approximate_shopping_time' => $this->approximate_shopping_time . ' min' . ($this->approximate_shopping_time > 1 ? 's' : ''),
            'delivery_fee' => $this->delivery_fee->getAmount()->toFloat(),
            'created_at' => $this->created_at,
        ];
    }
}
