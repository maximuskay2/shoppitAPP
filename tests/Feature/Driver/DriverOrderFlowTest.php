<?php

namespace Tests\Feature\Driver;

use App\Modules\Commerce\Events\OrderCompleted;
use App\Modules\Commerce\Events\OrderDispatched;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Settings;
use App\Modules\User\Enums\UserKYBStatusEnum;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_accept_and_deliver_order(): void
    {
        Event::fake([OrderCompleted::class, OrderDispatched::class]);

        Settings::create([
            'id' => Str::uuid(),
            'name' => 'currency',
            'value' => 'NGN',
        ]);

        $driver = User::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $driver->driver()->create([
            'id' => Str::uuid(),
            'is_verified' => true,
            'is_online' => true,
        ]);

        $vendorUser = User::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);
        $vendor = Vendor::create([
            'id' => Str::uuid(),
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Vendor',
            'kyb_status' => UserKYBStatusEnum::PENDING->value,
        ]);

        $customer = User::factory()->create([
            'status' => UserStatusEnum::ACTIVE->value,
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'id' => Str::uuid(),
            'user_id' => $customer->id,
            'vendor_id' => $vendor->id,
            'status' => 'READY_FOR_PICKUP',
            'email' => $customer->email,
            'tracking_id' => 'ORD-TEST1234',
            'payment_reference' => 'PAY-TEST-1',
            'processor_transaction_id' => 'PROC-TEST-1',
            'delivery_fee' => 500,
            'gross_total_amount' => 5000,
            'net_total_amount' => 4500,
            'otp_code' => '1234',
        ]);

        Sanctum::actingAs($driver);

        $this->getJson(route('driver.orders.available'))
            ->assertOk()
            ->assertJsonFragment(['id' => $order->id]);

        $this->postJson(route('driver.orders.accept', ['orderId' => $order->id]))
            ->assertOk()
            ->assertJsonFragment(['driver_id' => $driver->id]);

        $this->postJson(route('driver.orders.pickup', ['orderId' => $order->id]))
            ->assertOk()
            ->assertJsonFragment(['status' => 'PICKED_UP']);

        $this->postJson(route('driver.orders.out.for.delivery', ['orderId' => $order->id]))
            ->assertOk()
            ->assertJsonFragment(['status' => 'OUT_FOR_DELIVERY']);

        $this->postJson(route('driver.orders.deliver', ['orderId' => $order->id]), [
            'otp_code' => '1234',
        ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'DELIVERED']);
    }
}
