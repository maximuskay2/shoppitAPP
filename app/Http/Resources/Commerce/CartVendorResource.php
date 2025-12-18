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
            'vendor_total' => $this->total(),
            'item_count' => $this->items->count(),
        ];
    }
}
