<?php

namespace App\Modules\Commerce\Services\Products;

use App\Modules\Commerce\Models\ProductCategory;
use App\Modules\User\Models\Vendor;
use Illuminate\Support\Facades\Log;

class ProductCategoryService
{
    /**
     * Get all product categories for a vendor
     * 
     * @param Vendor $vendor
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVendorCategories(Vendor $vendor)
    {
        return $vendor->productCategories()->orderBy('created_at', 'desc')->get();
    }

    /**
     * Create product category
     * 
     * @param Vendor $vendor
     * @param array $attributes
     * @return ProductCategory
     */
    public function createProductCategory(Vendor $vendor, array $attributes)
    {
        $category = ProductCategory::create([
            'vendor_id' => $vendor->id,
            'name' => $attributes['name'],
            'avatar' => $attributes['avatar'] ?? null,
            'description' => $attributes['description'] ?? null,
            'is_active' => $attributes['is_active'] ?? true,
        ]);
        
        return $category;
    }

    /**
     * Update product category
     * 
     * @param ProductCategory $category
     * @param array $attributes
     * @return ProductCategory
     */
    public function updateProductCategory(ProductCategory $category, array $attributes)
    {
        $updates = [
            'name' => $attributes['name'] ?? $category->name,
            'avatar' => $attributes['avatar'] ?? $category->avatar,
            'description' => $attributes['description'] ?? $category->description,
            'is_active' => $attributes['is_active'] ?? $category->is_active,
        ];

        $category->update($updates);
        $category->refresh();

        return $category;
    }

    /**
     * Delete product category
     * 
     * @param ProductCategory $category
     * @return bool
     */
    public function deleteProductCategory(ProductCategory $category)
    {
        return $category->delete();
    }

    /**
     * Find product category by ID for vendor
     * 
     * @param string $id
     * @param Vendor $vendor
     * @return ProductCategory|null
     */
    public function findCategoryById(string $id, Vendor $vendor)
    {
        return ProductCategory::where('id', $id)
            ->where('vendor_id', $vendor->id)
            ->first();
    }
}
