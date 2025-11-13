<?php

namespace App\Http\Resources\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'product_category_id' => $this->product_category_id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'approximate_delivery_time' => $this->approximate_delivery_time,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
        ];
    }
}
