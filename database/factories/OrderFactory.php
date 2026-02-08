<?php

namespace Database\Factories;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Commerce\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        Settings::firstOrCreate(
            ['name' => 'currency'],
            ['value' => 'NGN']
        );

        return [
            'user_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'status' => 'READY_FOR_PICKUP',
            'email' => fake()->safeEmail(),
            'tracking_id' => 'TRK-' . Str::uuid(),
            'payment_reference' => 'PAY-' . Str::uuid(),
            'processor_transaction_id' => 'PROC-' . Str::uuid(),
            'currency' => 'NGN',
            'delivery_fee' => 500,
            'gross_total_amount' => 1500,
            'net_total_amount' => 1500,
            'coupon_discount' => 0,
            'otp_code' => '123456',
            'delivery_latitude' => 6.5244,
            'delivery_longitude' => 3.3792,
        ];
    }
}
