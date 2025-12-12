<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory, UUID;

    protected $table = 'coupons';

    protected $guarded = [];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_active' => 'boolean',
        'discount_amount' => TXAmountCast::class,
        'minimum_order_value' => TXAmountCast::class,
        'maximum_discount' => TXAmountCast::class,
        'usage_per_customer' => 'integer',
        'usage_count' => 'integer',
        'percent' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    /**
     * Check if coupon is valid for a user
     */
    public function isValidForUser($userId): bool
    {
        if (!$this->is_active || !$this->is_visible) {
            return false;
        }

        // Check usage limit per customer
        $userUsageCount = $this->usages()->where('user_id', $userId)->count();

        return $userUsageCount < $this->usage_per_customer;
    }

    /**
     * Calculate discount amount for given order total
     */
    public function calculateDiscount($orderTotal): float
    {
        if ($this->discount_type === 'flat') {
            return min($this->discount_amount, $orderTotal);
        } elseif ($this->discount_type === 'percent') {
            $discount = ($orderTotal * $this->percent) / 100;
            return $this->maximum_discount ? min($discount, $this->maximum_discount) : $discount;
        }

        return 0;
    }

    /**
     * Check if coupon can be applied to order total
     */
    public function canApplyToOrder($orderTotal): bool
    {
        return $orderTotal >= $this->minimum_order_value;
    }
}