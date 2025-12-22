<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Transaction\Casts\TXAmountCast;
use App\Modules\User\Models\Vendor;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;

class CartVendor extends Model
{
    use UUID;

    protected $table = 'cart_vendors';
    protected $guarded = [];

    protected $casts = [
        'coupon_discount' => TXAmountCast::class,
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get vendor subtotal (before discount)
     */
    public function subtotal()
    {
        return $this->items->sum(function ($item) {
            return $item->subtotal->getAmount()->toFloat();
        });
    }

    /**
     * Get vendor total (after discount)
     */
    public function total()
    {
        $subtotal = $this->subtotal();
        $discount = $this->coupon_discount ? $this->coupon_discount->getAmount()->toFloat() : 0;
        return max(0, $subtotal - $discount);
    }

    /**
     * Get coupon discount amount
     */
    public function discountAmount()
    {
        return $this->coupon_discount ? $this->coupon_discount->getAmount()->toFloat() : 0;
    }
}
