<?php

namespace Tests\Feature\Driver;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverGeoFenceValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $customer;
    protected Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::create([
            'name' => 'currency',
            'value' => 'NGN',
        ]);

        $this->driver = $this->createDriverUser();
        $this->customer = $this->createActiveUser();
        $this->vendor = $this->createVendor();
    }

    public function test_pickup_requires_driver_within_geofence(): void
    {
        $order = $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'READY_FOR_PICKUP',
            'assigned_at' => now(),
        ]);

        $this->createDriverLocation(12.0, 8.0);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson(route('driver.orders.pickup', ['orderId' => $order->id], false));

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Driver must be within 300 km of the vendor to pick up the order.');
    }

    public function test_delivery_requires_driver_within_geofence(): void
    {
        $order = $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'OUT_FOR_DELIVERY',
            'assigned_at' => now(),
            'delivery_latitude' => 6.5244,
            'delivery_longitude' => 3.3792,
        ]);

        $this->createDriverLocation(12.0, 8.0);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson(route('driver.orders.deliver', ['orderId' => $order->id], false), [
                'otp_code' => $order->otp_code,
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'Driver must be within 300 km of the delivery address to complete the order.');
    }

    public function test_pickup_succeeds_within_geofence(): void
    {
        $order = $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'READY_FOR_PICKUP',
            'assigned_at' => now(),
        ]);

        $this->createDriverLocation(6.52445, 3.37925);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson(route('driver.orders.pickup', ['orderId' => $order->id], false));

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'PICKED_UP');
    }

    private function createDriverUser(): User
    {
        $user = User::factory()->create([
            'status' => UserStatusEnum::ACTIVE,
            'email_verified_at' => now(),
        ]);

        Driver::create([
            'user_id' => $user->id,
            'is_verified' => true,
            'is_online' => true,
        ]);

        return $user;
    }

    private function createActiveUser(): User
    {
        return User::factory()->create([
            'status' => UserStatusEnum::ACTIVE,
            'email_verified_at' => now(),
        ]);
    }

    private function createVendor(): Vendor
    {
        $vendorUser = $this->createActiveUser();

        return Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Vendor',
            'delivery_fee' => 0,
            'latitude' => 6.5244,
            'longitude' => 3.3792,
        ]);
    }

    private function createOrder(array $overrides = []): Order
    {
        $payload = array_merge([
            'user_id' => $this->customer->id,
            'vendor_id' => $this->vendor->id,
            'status' => 'READY_FOR_PICKUP',
            'email' => $this->customer->email,
            'tracking_id' => 'TRK-' . Str::uuid(),
            'payment_reference' => 'PAY-' . Str::uuid(),
            'processor_transaction_id' => 'PROC-' . Str::uuid(),
            'currency' => 'NGN',
            'delivery_fee' => 500,
            'gross_total_amount' => 1500,
            'net_total_amount' => 1500,
            'coupon_discount' => 0,
            'otp_code' => '123456',
        ], $overrides);

        return Order::create($payload);
    }

    private function createDriverLocation(float $lat, float $lng): DriverLocation
    {
        return DriverLocation::create([
            'user_id' => $this->driver->id,
            'lat' => $lat,
            'lng' => $lng,
            'recorded_at' => now(),
        ]);
    }
}
