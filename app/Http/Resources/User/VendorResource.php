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
            'created_at' => $this->created_at,
        ];
    }
}
