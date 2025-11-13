<?php

namespace App\Modules\Commerce\Services\Products;

use App\Modules\Commerce\Models\Product;
use App\Modules\User\Models\Vendor;
use Illuminate\Support\Facades\Log;

class ProductService
{
    /**
     * Get all products for a vendor
     * 
     * @param Vendor $vendor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVendorProducts(Vendor $vendor)
    {
        return $vendor->products()->with('category')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create product
     * 
     * @param Vendor $vendor
     * @param array $attributes
     * @return Product
     */
    public function createProduct(Vendor $vendor, array $attributes)
    {
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'product_category_id' => $attributes['product_category_id'],
            'name' => $attributes['name'],
            'avatar' => $attributes['avatar'] ?? null,
            'description' => $attributes['description'] ?? null,
            'price' => $attributes['price'],
            'discount_price' => $attributes['discount_price'] ?? 0.00,
            'approximate_delivery_time' => $attributes['approximate_delivery_time'] ?? 0,
            'is_available' => $attributes['is_available'] ?? true,
        ]);
        
        return $product;
    }

    /**
     * Update product
     * 
     * @param Product $product
     * @param array $attributes
     * @return Product
     */
    public function updateProduct(Product $product, array $attributes)
    {
        $updates = [
            'product_category_id' => $attributes['product_category_id'] ?? $product->product_category_id,
            'name' => $attributes['name'] ?? $product->name,
            'avatar' => $attributes['avatar'] ?? $product->avatar,
            'description' => $attributes['description'] ?? $product->description,
            'price' => $attributes['price'] ?? $product->price,
            'discount_price' => $attributes['discount_price'] ?? $product->discount_price,
            'approximate_delivery_time' => $attributes['approximate_delivery_time'] ?? $product->approximate_delivery_time,
            'is_available' => $attributes['is_available'] ?? $product->is_available,
        ];

        $product->update($updates);
        $product->refresh();

        return $product;
    }

    /**
     * Delete product
     * 
     * @param Product $product
     * @return bool
     */
    public function deleteProduct(Product $product)
    {
        return $product->delete();
    }

    /**
     * Find product by ID for vendor
     * 
     * @param string $id
     * @param Vendor $vendor
     * @return Product|null
     */
    public function findProductById(string $id, Vendor $vendor)
    {
        return Product::where('id', $id)
            ->where('vendor_id', $vendor->id)
            ->first();
    }
}
