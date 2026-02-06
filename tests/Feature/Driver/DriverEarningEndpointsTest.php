<?php

namespace Tests\Feature\Driver;

use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use App\Modules\Commerce\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DriverEarningEndpointsTest extends TestCase
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

        $firstOrder = $this->createOrder();
        $secondOrder = $this->createOrder();

        DriverEarning::create([
            'driver_id' => $this->driver->id,
            'order_id' => $firstOrder->id,
            'gross_amount' => 1000,
            'commission_amount' => 100,
            'net_amount' => 900,
            'currency' => 'NGN',
            'status' => 'PENDING',
        ]);

        DriverEarning::create([
            'driver_id' => $this->driver->id,
            'order_id' => $secondOrder->id,
            'gross_amount' => 2000,
            'commission_amount' => 200,
            'net_amount' => 1800,
            'currency' => 'NGN',
            'status' => 'PAID',
        ]);
    }

    public function test_driver_earnings_summary(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson(route('driver.earnings.summary', [], false));

        $response->assertStatus(200);
        $response->assertJsonPath('data.totals.gross', 3000);
        $response->assertJsonPath('data.totals.commission', 300);
        $response->assertJsonPath('data.totals.net', 2700);
        $response->assertJsonPath('data.by_status.pending', 900);
        $response->assertJsonPath('data.by_status.paid', 1800);
    }

    public function test_driver_earnings_history(): void
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson(route('driver.earnings.history', [], false));

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
            'status' => 'DELIVERED',
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
