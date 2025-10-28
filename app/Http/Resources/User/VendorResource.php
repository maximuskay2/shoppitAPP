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
            'name' => $this->name,
            'business_name' => $this->vendor->business_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at,
            'kyb_status' => $this->vendor->kyb_status,
            'username' => $this->username,
            'address' => $this->address,
            'address_2' => $this->address_2,
            'city' => $this->city,
            'country' => $this->country,
            'created_at' => $this->created_at,
        ];
    }
}
