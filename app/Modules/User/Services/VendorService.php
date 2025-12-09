<?php

namespace App\Modules\User\Services;

use App\Http\Resources\User\UserResource;
use App\Modules\User\Models\Vendor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VendorService
{
    /**
     * Update vendor account
     * 
     * @param Vendor|Authenticatable $vendor
     * @param array $attributes
     * @return Vendor
     */
    public function updateVendorAccount($vendor, $attributes)
    {
        $updates = [
            'business_name' => $attributes['business_name'] ?? $vendor->business_name,
            'kyb_status' => $attributes['kyb_status'] ?? $vendor->kyb_status,
            'tin' => $attributes['tin'] ?? $vendor->tin,
            'cac' => $attributes['cac'] ?? $vendor->cac,
            'cloudinary_public_id' => $attributes['cloudinary_public_id'] ?? $vendor->cloudinary_public_id,
            'rejection_reason' => $attributes['rejection_reason'] ?? $vendor->rejection_reason,
            'admin_notes' => $attributes['admin_notes'] ?? $vendor->admin_notes,
            'opening_time' => $attributes['opening_time'] ?? $vendor->opening_time,
            'closing_time' => $attributes['closing_time'] ?? $vendor->closing_time,
            'delivery_fee' => $attributes['delivery_fee'] ?? $vendor->delivery_fee,
            'approximate_shopping_time' => $attributes['approximate_shopping_time'] ?? $vendor->approximate_shopping_time,
        ];

        $vendor->update($updates);
        $vendor->refresh();

        return $vendor;
    }
    /**
     * Create vendor account
     * 
     * @param Vendor|Authenticatable $vendor
     * @param array $attributes
     * @return Vendor
     */
    public function createVendorAccount($user, $attributes)
    {
        $vendor = $user->vendor;
        if ($vendor) {
            throw new \InvalidArgumentException('Vendor account already exists for this user');
        }

        $vendor = Vendor::create([
            'user_id' => $user->id,
            'business_name' => $attributes['business_name'] ?? null,
            'kyb_status' => $attributes['kyb_status'] ?? null,
            'tin' => $attributes['tin'] ?? null,
            'cac' => $attributes['cac'] ?? null,
            'cloudinary_public_id' => $attributes['cloudinary_public_id'] ?? null,
            'rejection_reason' => $attributes['rejection_reason'] ?? null,
            'admin_notes' => $attributes['admin_notes'] ?? null,
            'opening_time' => $attributes['opening_time'] ?? null,
            'closing_time' => $attributes['closing_time'] ?? null,
            'delivery_fee' => $attributes['delivery_fee'] ?? 0.00,
            'approximate_shopping_time' => $attributes['approximate_shopping_time'] ?? 0,
        ]);
        
        return $vendor;
    }
}
