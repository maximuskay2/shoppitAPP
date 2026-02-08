<?php

namespace Tests\Feature\Driver;

use App\Modules\Commerce\Models\DeliveryRadius;
use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\Driver;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverOrderLocationFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $driver;
    protected User $vendor;
    protected Vendor $vendorProfile;
    protected Order $order;

    /**
     * Setup test fixtures
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create delivery radius configuration
        DeliveryRadius::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'name' => 'default',
            'radius_km' => 15,
            'is_active' => true,
        ]);

        // Create driver user
        $this->driver = User::factory()->create();
        Driver::factory()->create(['user_id' => $this->driver->id]);

        // Create vendor user
        $this->vendor = User::factory()->create();
        $this->vendorProfile = Vendor::factory()->create([
            'user_id' => $this->vendor->id,
            'latitude' => 6.5210, // Lagos
            'longitude' => 3.3820,
        ]);

        // Create an order ready for pickup
        $this->order = Order::factory()->create([
            'vendor_id' => $this->vendorProfile->id,
            'status' => 'READY_FOR_PICKUP',
            'driver_id' => null,
        ]);
    }

    /**
     * Test driver can view available orders without location filter
     */
    public function test_driver_can_view_available_orders_without_location()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.id', $this->order->id);
    }

    /**
     * Test driver receives only orders within delivery radius
     */
    public function test_driver_receives_only_orders_within_delivery_radius()
    {
        // Driver is 3km away from vendor (within 15km radius)
        $driverLat = 6.5244;
        $driverLon = 3.3792;

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available?latitude=' . $driverLat . '&longitude=' . $driverLon);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
    }

    /**
     * Test driver doesn't see orders outside delivery radius
     */
    public function test_driver_does_not_see_orders_outside_radius()
    {
        // Create another vendor far away (500km away)
        $farVendor = Vendor::factory()->create([
            'user_id' => User::factory()->create()->id,
            'latitude' => 9.0765, // Abuja
            'longitude' => 7.3986,
        ]);

        $farOrder = Order::factory()->create([
            'vendor_id' => $farVendor->id,
            'status' => 'READY_FOR_PICKUP',
            'driver_id' => null,
        ]);

        // Driver location
        $driverLat = 6.5244;
        $driverLon = 3.3792;

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available?latitude=' . $driverLat . '&longitude=' . $driverLon);

        $response->assertStatus(200);
        // Should only see nearby order, not far order
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.id', $this->order->id);
    }

    /**
     * Test vendor filter works with location filtering
     */
    public function test_vendor_filter_combined_with_location()
    {
        // Create another vendor nearby but don't care about distance
        $otherVendor = Vendor::factory()->create([
            'user_id' => User::factory()->create()->id,
            'latitude' => 6.5200,
            'longitude' => 3.3800,
        ]);

        $otherOrder = Order::factory()->create([
            'vendor_id' => $otherVendor->id,
            'status' => 'READY_FOR_PICKUP',
            'driver_id' => null,
        ]);

        // Driver location
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available?latitude=6.5244&longitude=3.3792&vendor_id=' . $this->vendorProfile->id);

        $response->assertStatus(200);
        // Should only see order from specified vendor
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.vendor.id', $this->vendorProfile->id);
    }

    /**
     * Test driver order includes OTP code
     */
    public function test_available_order_includes_otp()
    {
        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available');

        $response->assertStatus(200);
        $response->assertJsonPath('data.data.0.otp_code', $this->order->otp_code);
    }

    /**
     * Test pagination works with location filtering
     */
    public function test_pagination_works_with_location_filtering()
    {
        // Create 25 orders (more than page size of 20)
        for ($i = 0; $i < 24; $i++) {
            Order::factory()->create([
                'vendor_id' => $this->vendorProfile->id,
                'status' => 'READY_FOR_PICKUP',
                'driver_id' => null,
            ]);
        }

        $response = $this->actingAs($this->driver, 'sanctum')
            ->getJson('/api/v1/driver/orders/available?latitude=6.5244&longitude=3.3792');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data.data');
        $response->assertJsonPath('data.next_cursor', fn($cursor) => $cursor !== null);
    }
}
