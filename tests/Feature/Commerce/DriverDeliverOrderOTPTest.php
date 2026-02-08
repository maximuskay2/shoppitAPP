<?php

namespace Tests\Feature\Commerce;

use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DriverDeliverOrderOTPTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected Order $order;

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([\App\Modules\Commerce\Events\OrderCompleted::class]);

        $this->driver = User::factory()->create();
        Driver::factory()->create([
            'user_id' => $this->driver->id,
            'is_verified' => true,
            'is_online' => true,
        ]);
        DriverLocation::create([
            'user_id' => $this->driver->id,
            'lat' => 6.5244,
            'lng' => 3.3792,
            'recorded_at' => now(),
        ]);

        $this->order = Order::factory()->create([
            'status' => 'OUT_FOR_DELIVERY',
            'driver_id' => $this->driver->id,
            'otp_code' => '123456',
            'delivery_latitude' => 6.5244,
            'delivery_longitude' => 3.3792,
        ]);
    }

    /**
     * Test driver cannot deliver order with incorrect OTP
     */
    public function test_driver_cannot_deliver_with_incorrect_otp()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '999999',
            ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', 'The OTP code provided is incorrect.');
    }

    /**
     * Test driver can deliver order with correct OTP
     */
    public function test_driver_can_deliver_with_correct_otp()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '123456',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'DELIVERED');
    }

    /**
     * Test OTP is not required if order has no OTP
     */
    public function test_otp_not_required_for_delivery_without_otp()
    {
        $orderNoOtp = Order::factory()->create([
            'status' => 'OUT_FOR_DELIVERY',
            'driver_id' => $this->driver->id,
            'otp_code' => null,
            'delivery_latitude' => 6.5244,
            'delivery_longitude' => 3.3792,
        ]);

        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$orderNoOtp->id}/deliver", []);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'DELIVERED');
    }

    /**
     * Test driver earns commission when delivering
     */
    public function test_driver_earning_recorded_on_delivery()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '123456',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.driver_earning.net_amount', fn($amount) => $amount > 0);
    }

    /**
     * Test OTP validation rules
     */
    public function test_otp_validation_rules()
    {
        // Too short
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '123',
            ]);

        $response->assertStatus(422);

        // Non-numeric
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => 'abcd12',
            ]);

        $response->assertStatus(422);

        // Too long
        $response = $this->actingAs($this->driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '12345678901',
            ]);

        $response->assertStatus(422);
    }
}
