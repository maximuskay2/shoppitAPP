<?php

namespace Tests\Feature\Commerce;

use App\Helpers\OTPHelper;
use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->driver = User::factory()->create();
        
        $this->order = Order::factory()->create([
            'status' => 'PICKED_UP',
            'driver_id' => $this->driver->id,
            'otp_code' => '123456',
        ]);
    }

    /**
     * Test driver cannot deliver order with incorrect OTP
     */
    public function test_driver_cannot_deliver_with_incorrect_otp()
    {
        $response = $this->actingAs($this->driver)
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '999999',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The OTP code provided is incorrect.');
    }

    /**
     * Test driver can deliver order with correct OTP
     */
    public function test_driver_can_deliver_with_correct_otp()
    {
        $response = $this->actingAs($this->driver)
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
            'status' => 'PICKED_UP',
            'driver_id' => $this->driver->id,
            'otp_code' => null,
        ]);

        $response = $this->actingAs($this->driver)
            ->postJson("/api/v1/driver/orders/{$orderNoOtp->id}/deliver", []);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'DELIVERED');
    }

    /**
     * Test driver earns commission when delivering
     */
    public function test_driver_earning_recorded_on_delivery()
    {
        $response = $this->actingAs($this->driver)
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
        $response = $this->actingAs($this->driver)
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '123',
            ]);

        $response->assertStatus(422);

        // Non-numeric
        $response = $this->actingAs($this->driver)
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => 'abcd12',
            ]);

        $response->assertStatus(422);

        // Too long
        $response = $this->actingAs($this->driver)
            ->postJson("/api/v1/driver/orders/{$this->order->id}/deliver", [
                'otp_code' => '12345678901',
            ]);

        $response->assertStatus(422);
    }
}
