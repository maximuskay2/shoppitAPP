<?php

namespace App\Http\Resources\Commerce;

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
            'name' => $this->business_name,
            'phone' => $this->user->phone,
            'avatar' => $this->user->avatar,
            'address' => $this->user->address,
            'address_2' => $this->user->address_2,
            'city' => $this->user->city,
            'state' => $this->user->state,
            'country' => $this->user->country,
            'opening_time' => $this->opening_time?->format('g:i A'),
            'closing_time' => $this->closing_time?->format('g:i A'),
            'is_open' => $this->isOpen(),
            'approximate_shopping_time' => $this->approximate_shopping_time . ' min' . ($this->approximate_shopping_time > 1 ? 's' : ''),
            'delivery_fee' => $this->delivery_fee->getAmount()->toFloat(),
            'average_rating' => $this->averageRating(),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
