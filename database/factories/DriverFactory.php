<?php

namespace Database\Factories;

use App\Modules\User\Models\Driver;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\User\Models\Driver>
 */
class DriverFactory extends Factory
{
    protected $model = Driver::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_type' => fake()->randomElement(['bike', 'car', 'van']),
            'license_number' => strtoupper(fake()->bothify('LIC-####')),
            'is_verified' => true,
            'is_online' => true,
            'verified_at' => now(),
        ];
    }
}
