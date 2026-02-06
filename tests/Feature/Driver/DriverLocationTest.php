<?php

namespace Tests\Feature\Driver;

use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_update_location(): void
    {
        $driver = User::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $driver->driver()->create([
            'id' => Str::uuid(),
            'is_verified' => true,
            'is_online' => true,
        ]);

        Sanctum::actingAs($driver);

        $this->postJson(route('driver.location.update'), [
            'lat' => 6.5244,
            'lng' => 3.3792,
            'bearing' => 120.5,
        ])->assertOk();
    }
}
