<?php

namespace App\Http\Resources\Commerce;

use App\Http\Resources\User\VendorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleProductResource extends JsonResource
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
            'price' => $this->price->getAmount()->toFloat(),
            'discount_price' => $this->discount_price->getAmount()->toFloat(),
            'approximate_delivery_time' => $this->approximate_delivery_time,
            'is_available' => $this->is_available,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'rating' => $this->reviews()->avg('rating'),
            'first_review' => new ReviewResource($this->reviews->first()),
            'reviews_count' => $this->averageRating(),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
