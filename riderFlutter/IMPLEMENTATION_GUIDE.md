# ShopIT Plus - Delivery System Implementation Guide

## Quick Start (15 minutes)

### 1. Run Database Migrations

```bash
php artisan migrate

# Verify migrations
php artisan migrate:status | grep "delivery\|vendors\|addresses\|orders"
```

**What's created:**
- `delivery_radii` table with default 15km radius
- `vendors.latitude`, `vendors.longitude` columns
- `addresses.latitude`, `addresses.longitude` columns  
- `orders.delivery_latitude`, `orders.delivery_longitude`, `orders.otp_code` columns

### 2. Configure Delivery Radius

```bash
# Set delivery radius to 20km
php artisan delivery:setup-radius --radius=20

# View current configuration
php artisan delivery:setup-radius
```

### 3. Populate Geographic Data

For existing vendors and addresses, you'll need to geocode them:

```php
<?php
// In tinker or a migration

// Update vendors with coordinates
$vendor = Vendor::find($vendorId);
$vendor->update([
    'latitude' => 6.5210,
    'longitude' => 3.3820,
]);

// Update addresses with coordinates
$address = Address::find($addressId);
$address->update([
    'latitude' => 6.5300,
    'longitude' => 3.3900,
]);
```

**Using Google Maps API to geocode addresses:**

```php
<?php

use GuzzleHttp\Client;

class GeocodeService {
    public function geocodeAddress(string $address): array {
        $client = new Client();
        $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'address' => $address,
                'key' => config('services.google.maps_api_key'),
            ]
        ]);
        
        $results = json_decode($response->getBody(), true)['results'];
        if (empty($results)) {
            return ['latitude' => null, 'longitude' => null];
        }
        
        $location = $results[0]['geometry']['location'];
        return [
            'latitude' => $location['lat'],
            'longitude' => $location['lng'],
        ];
    }
}

// Usage
$geocoder = new GeocodeService();
$coords = $geocoder->geocodeAddress('123 Lekki, Lagos, Nigeria');
$address->update($coords);
```

### 4. Test the System

```bash
# Run unit tests
php artisan test tests/Unit/Helpers/GeoHelperTest.php
php artisan test tests/Unit/Helpers/OTPHelperTest.php

# Run feature tests
php artisan test tests/Feature/Driver/DriverOrderLocationFilteringTest.php
php artisan test tests/Feature/Commerce/DriverDeliverOrderOTPTest.php
```

---

## Detailed Implementation

### Phase 1: Database Setup

#### Migration Files Created

1. **`2026_02_07_100000_add_geo_columns_to_vendors_table.php`**
   - Adds `latitude` and `longitude` to vendors table
   - Creates index for coordinate queries

2. **`2026_02_07_100001_add_geo_columns_to_addresses_table.php`**
   - Adds `latitude` and `longitude` to addresses table
   - Creates index for fast geographic queries

3. **`2026_02_07_100002_add_geo_and_otp_columns_to_orders_table.php`**
   - Adds `delivery_latitude` and `delivery_longitude` to store drop-off location
   - Adds `cancelled_at` and `cancellation_reason` for tracking cancellations
   - Creates index for delivery coordinate lookups

4. **`2026_02_07_100003_create_delivery_radii_table.php`**
   - Creates new table for delivery radius configuration
   - Inserts default 15km radius

#### Run Migrations

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/shopittplus-api

# Run all pending migrations
php artisan migrate

# Or specific migration
php artisan migrate --path=database/migrations/2026_02_07_100000_add_geo_columns_to_vendors_table.php
```

### Phase 2: Helper Functions

#### GeoHelper (`app/Helpers/GeoHelper.php`)

**Key Methods:**

```php
// Calculate distance between two coordinates (Haversine formula)
$distanceKm = GeoHelper::calculateDistance($lat1, $lon1, $lat2, $lon2);

// Check if point is within delivery radius
$isWithin = GeoHelper::isWithinDeliveryRadius($driverLat, $driverLon, $targetLat, $targetLon);

// Get active delivery radius (default 15km)
$radiusKm = GeoHelper::getActiveDeliveryRadius();

// Get bounding box for database optimization
$bbox = GeoHelper::getBoundingBox($centerLat, $centerLon, $radiusKm);

// Format distance for display
echo GeoHelper::formatDistance(15.75); // Output: "15.75 km"
```

#### OTPHelper (`app/Helpers/OTPHelper.php`)

**Key Methods:**

```php
// Generate random 6-digit OTP
$otp = OTPHelper::generate(6); // Returns: "428516"

// Validate OTP format
if (OTPHelper::validate($userInput)) { /* ... */ }

// Compare OTPs (constant-time to prevent timing attacks)
if (OTPHelper::compare($providedOtp, $storedOtp)) { /* ... */ }
```

### Phase 3: Model Updates

#### Order Model

```php
protected $casts = [
    // ... existing casts
    'delivery_latitude' => 'decimal:7',
    'delivery_longitude' => 'decimal:7',
    'cancelled_at' => 'datetime',
];
```

Attributes available:
- `otp_code`: 4-10 digit OTP for delivery verification
- `delivery_latitude`: Drop-off location latitude
- `delivery_longitude`: Drop-off location longitude
- `cancelled_at`: Timestamp of cancellation
- `cancellation_reason`: Reason for order cancellation

#### Vendor Model

```php
protected $casts = [
    // ... existing casts
    'latitude' => 'decimal:7',
    'longitude' => 'decimal:7',
];
```

Attributes available:
- `latitude`: Vendor shop/warehouse pickup location
- `longitude`: Vendor shop/warehouse pickup location

#### Address Model

```php
protected $casts = [
    // ... existing casts
    'latitude' => 'decimal:7',
    'longitude' => 'decimal:7',
];
```

Attributes available:
- `latitude`: Delivery address latitude
- `longitude`: Delivery address longitude

### Phase 4: Service Layer Enhancements

#### OrderProcessedListener Updates

**New Functionality:**
- Generates OTP when order is created
- Captures delivery address coordinates from Address model
- Includes OTP in order notifications

**Example Flow:**

```
User Places Order
    ↓
CartService.processCart() fires OrderProcessed event
    ↓
OrderProcessedListener.handle()
    ├─ Create Order with OTP
    ├─ Update with delivery coordinates
    ├─ Process payment (wallet or pending)
    └─ Send notifications with OTP
```

#### DriverOrderService Updates

**New availableOrders() Logic:**

```php
// Without location (backward compatible)
GET /driver/orders/available
→ Returns all READY_FOR_PICKUP orders

// With location (geo-filtered)
GET /driver/orders/available?latitude=6.5244&longitude=3.3792
→ Returns only orders within delivery radius of driver location
→ Radius fetched from delivery_radii table (default 15km)

// With vendor filter
GET /driver/orders/available?vendor_id=VENDOR_UUID&latitude=6.5244&longitude=3.3792
→ Returns filtered results from specific vendor Only
```

**Database Query Optimization:**

```php
// 1. Use bounding box for initial filtering (fast)
$boundingBox = GeoHelper::getBoundingBox($driverLat, $driverLon, $radiusKm);
$query->whereBetween('vendors.latitude', [$bbox['lat_min'], $bbox['lat_max']]);
$query->whereBetween('vendors.longitude', [$bbox['lon_min'], $bbox['lon_max']]);

// 2. Then apply Haversine for precise distance
$query->where(DB::raw('SQRT(POW(...)...'), '<=', $radiusKm);
```

### Phase 5: Notification Updates

#### Updated Notifications

**OrderPlacedSuccessfullyNotification** includes:
- Email: OTP in template context
- FCM: OTP in data payload
- Database: OTP in notification array

**OrderDispatchedNotification** includes:
- Email: OTP for customer reference
- FCM: OTP for mobile app display
- Database: OTP for app state

Example notification data:
```json
{
  "order_id": "550e8400-e29b-41d4-a716-446655440000",
  "tracking_id": "SPLUS-20240207-001",
  "otp": "428516",  // New field
  "status": "OUT_FOR_DELIVERY"
}
```

---

## Integration Examples

### Frontend: React/TypeScript

#### Place Order with Delivery Address

```typescript
// Hook to get user's delivery location
const [deliveryLocation, setDeliveryLocation] = useState<{
  latitude: number;
  longitude: number;
} | null>(null);

// Get location from address selection
const handleAddressSelect = async (addressId: string) => {
  const response = await fetch(`/api/v1/user/addresses/${addressId}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const { data } = await response.json();
  
  setDeliveryLocation({
    latitude: data.latitude,
    longitude: data.longitude
  });
};

// Place order
const placeOrder = async () => {
  const response = await fetch('/api/v1/user/cart/process', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      vendor_id: selectedVendor.id,
      receiver_delivery_address_id: selectedAddress.id,
      payment_method: 'wallet',
      wallet_usage: true
    })
  });
  
  const result = await response.json();
  console.log('Order created with OTP:', result.data.otp_code);
};
```

#### Driver: Browse & Accept Nearby Orders

```typescript
// Get driver's current location
const getCurrentLocation = (): Promise<Coordinates> => {
  return new Promise((resolve, reject) => {
    navigator.geolocation.getCurrentPosition(
      (position) => resolve(position.coords),
      reject
    );
  });
};

// Fetch nearby orders
const fetchNearbyOrders = async () => {
  const coords = await getCurrentLocation();
  
  const response = await fetch(
    `/api/v1/driver/orders/available?latitude=${coords.latitude}&longitude=${coords.longitude}`,
    {
      headers: { 'Authorization': `Bearer ${driverToken}` }
    }
  );
  
  const { data: orders } = await response.json();
  setAvailableOrders(orders);
};

// Accept specific order
const acceptOrder = async (orderId: string) => {
  const response = await fetch(`/api/v1/driver/orders/${orderId}/accept`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${driverToken}` }
  });
  
  const result = await response.json();
  console.log('Order accepted:', result.data);
};
```

#### Driver: Complete Delivery with OTP

```typescript
// Verify and complete delivery
const completeDelivery = async (orderId: string, otpCode: string) => {
  try {
    const response = await fetch(`/api/v1/driver/orders/${orderId}/deliver`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${driverToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        otp_code: otpCode
      })
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message);
    }
    
    const result = await response.json();
    console.log('Delivery completed!');
    console.log('Driver earning:', result.data.driver_earning);
    
  } catch (error) {
    console.error('Delivery verification failed:', error.message);
  }
};
```

### Backend: PHP/Laravel

#### Artisan Command to Setup Delivery

```bash
# Initialize delivery system
php artisan delivery:setup-radius --radius=20

# Reset to defaults
php artisan delivery:setup-radius --reset
```

#### Calculating Driver Availability & Earnings

```php
<?php

use App\Helpers\GeoHelper;
use App\Modules\Commerce\Services\Driver\DriverOrderService;

// Get driver's current location
$driverLat = 6.5244;
$driverLon = 3.3792;

// Fetch available orders for this driver
$availableOrders = (new DriverOrderService())->availableOrders(
    new Request([
        'latitude' => $driverLat,
        'longitude' => $driverLon
    ])
);

// Process each order availability
foreach ($availableOrders as $order) {
    $distance = GeoHelper::calculateDistance(
        $driverLat, $driverLon,
        $order->vendor->latitude, $order->vendor->longitude
    );
    
    echo "{$order->tracking_id}: {$distance}km away\n";
}
```

#### Batch Update Vendor Coordinates

```php
<?php

// Bulk geocode vendors
use App\Modules\User\Models\Vendor;

$vendors = Vendor::whereNull('latitude')->get();

foreach ($vendors as $vendor) {
    // Call your geocoding service
    $coords = geocodeAddress($vendor->sho_name);
    
    if ($coords) {
        $vendor->update([
            'latitude' => $coords['latitude'],
            'longitude' => $coords['longitude']
        ]);
    }
}

echo "Updated " . count($vendors) . " vendor coordinates";
```

---

## Monitoring & Maintenance

### Performance Monitoring

```sql
-- Check index usage for geographic queries
SELECT * FROM information_schema.STATISTICS 
WHERE TABLE_NAME IN ('vendors', 'addresses', 'orders')
AND COLUMN_NAME IN ('latitude', 'longitude')
ORDER BY SEQ_IN_INDEX;

-- Monitor slow queries related to distance calculations
SELECT * FROM mysql.slow_log 
WHERE query_time > 1
AND (query LIKE '%latitude%' OR query LIKE '%longitude%');
```

### Health Checks

```php
<?php

// CLI artisan command to verify system health
php artisan delivery:health-check

// Returns:
// ✓ Delivery radius configured: 15 km
// ✓ Vendors with coordinates: 45/50 (90%)
// ✓ Addresses with coordinates: 1200/1250 (96%)
// ✓ Recent orders with OTP: 98/100
// ✓ Last OTP generation: 2 minutes ago
```

---

## Troubleshooting

### Common Issues

1. **Orders not appearing after location update**
   - Check: Vendor has latitude/longitude set
   - Check: Delivery radius is configured
   - Check: Ensure indexes exist on coordinate columns

2. **OTP not in notifications**
   - Check: Order has otp_code populated
   - Check: OrderPlacedSuccessfullyNotification updated
   - Check: Queue is processing jobs: `php artisan queue:work`

3. **Slow location queries**
   - Check: Indexes exist: `SHOW INDEX FROM vendors`
   - Consider: Compound index on (latitude, longitude, deleted_at)
   - Profile: Use `EXPLAIN` on queries

4. **Distance calculations incorrect**
   - Verify: Coordinates are in decimal degrees (±180)
   - Check: Using correct latitude/longitude order
   - Test: Use known coordinates (e.g., Lagos to Abuja = ~492km)

---

## Next Steps

### Recommended Enhancements

1. **Real-time Driver Tracking**
   - Update DriverLocation every 30 seconds
   - WebSocket connection for live tracking
   - Admin map view with driver positions

2. **Smart Driver Matching**
   - Automatic assignment when order ready
   - Factor in driver rating & acceptance rate
   - ETA-based near optimal matching

3. **Delivery Route Optimization**
   - Multi-stop route planning
   - Minimize travel distance
   - Consider traffic patterns

4. **Analytics & Reporting**
   - Average delivery distance per driver
   - Earnings distribution analysis
   - Geo-heat maps of order density

---

## Support & Documentation

- **API Documentation**: See `DELIVERY_SYSTEM_DOCUMENTATION.md`
- **Tests**: Run `php artisan test` for full test suite
- **Issue Reporting**: Create GitHub issue with logs from `storage/logs/`

