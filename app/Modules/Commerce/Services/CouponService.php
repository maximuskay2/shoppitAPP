<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Coupon;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Models\Vendor;
use Brick\Money\Money;

class CouponService 
{
    public $currency;

    public function __construct() {
        $this->currency = Settings::where('name', 'currency')->first()->value;
    }

    public function index (Vendor $vendor)
    {
        return Coupon::where('vendor_id', $vendor->id)->paginate(15);
    }

    public function store (Vendor $vendor, array $data) {
        // Generate unique code if not provided
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        }

        return Coupon::create([
            'vendor_id' => $vendor->id,
            'code' => strtoupper($data['code']),
            'discount_type' => $data['discount_type'],
            'discount_amount' => Money::of($data['discount_amount'] ?? 0, $this->currency),
            'percent' => $data['percent'] ?? 0,
            'minimum_order_value' => Money::of($data['minimum_order_value'] ?? 0, $this->currency),
            'maximum_discount' => Money::of($data['maximum_discount'] ?? 0, $this->currency),
            'usage_per_customer' => $data['usage_per_customer'] ?? 1,
            'is_visible' => $data['is_visible'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function show (Vendor $vendor, string $id) {
        $coupon = Coupon::where('id', $id)->where('vendor_id', $vendor->id)->first();

        if (!$coupon) {
            throw new \InvalidArgumentException('Coupon not found or unauthorized access');
        }

        return $coupon;
    }

    public function update (Vendor $vendor, string $id, array $data) {
        $coupon = $this->show($vendor, $id);

        $coupon->update([
            'code' => isset($data['code']) ? strtoupper($data['code']) : $coupon->code,
            'discount_type' => $data['discount_type'] ?? $coupon->discount_type,
            'discount_amount' => isset($data['discount_amount']) ? Money::of($data['discount_amount'], $this->currency) : $coupon->discount_amount,
            'percent' => $data['percent'] ?? $coupon->percent,
            'minimum_order_value' => isset($data['minimum_order_value']) ? Money::of($data['minimum_order_value'], $this->currency) : $coupon->minimum_order_value,
            'maximum_discount' => isset($data['maximum_discount']) ? Money::of($data['maximum_discount'], $this->currency) : $coupon->maximum_discount,
            'usage_per_customer' => $data['usage_per_customer'] ?? $coupon->usage_per_customer,
            'is_visible' => $data['is_visible'] ?? $coupon->is_visible,
            'is_active' => $data['is_active'] ?? $coupon->is_active,
        ]);

        return $coupon;
    }

    public function delete (Vendor $vendor, string $id) {
        $coupon = $this->show($vendor, $id);
        $coupon->delete();
    }
    
    private function generateUniqueCode(): string
    {
        $maxTries = 10;
        $tries = 0;
        
        do {
            if ($tries >= $maxTries) {
                throw new \Exception('Unable to generate unique coupon code after ' . $maxTries . ' attempts');
            }
            
            $code = 'COUPON' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
            $tries++;
            
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}