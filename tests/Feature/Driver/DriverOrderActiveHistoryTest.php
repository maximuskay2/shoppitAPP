<?php

namespace Tests\Feature\Driver;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverOrderActiveHistoryTest extends TestCase
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

    public function test_driver_can_view_active_order(): void
    {
        $olderOrder = $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'READY_FOR_PICKUP',
            'assigned_at' => now()->subMinutes(30),
        ]);

        $latestOrder = $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'OUT_FOR_DELIVERY',
            'assigned_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson(route('driver.orders.active', [], false));

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $latestOrder->id);
        $response->assertJsonPath('data.status', 'OUT_FOR_DELIVERY');
    }

    public function test_driver_can_view_order_history(): void
    {
        $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'DELIVERED',
            'delivered_at' => now()->subDays(2),
        ]);

        $this->createOrder([
            'driver_id' => $this->driver->id,
            'status' => 'DELIVERED',
            'delivered_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson(route('driver.orders.history', [], false));

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.data');
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
        ], $overrides);

        return Order::create($payload);
    }
}
