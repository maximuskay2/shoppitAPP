<?php

namespace Database\Factories;

use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        Settings::firstOrCreate(
            ['name' => 'currency'],
            ['value' => 'NGN']
        );

        return [
            'user_id' => User::factory(),
            'business_name' => fake()->company(),
            'kyb_status' => UserKYBStatusEnum::PENDING->value,
            'delivery_fee' => 500,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ];
    }
}
