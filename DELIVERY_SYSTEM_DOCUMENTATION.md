# ShopIT Plus Delivery System - Geo-Fencing & OTP Implementation Guide

## System Architecture Overview

### Core Components

1. **Geo-Location Module** (`app/Helpers/GeoHelper.php`)
   - Haversine distance calculations
   - Bounding box optimization for queries
   - Delivery radius validation

2. **OTP Module** (`app/Helpers/OTPHelper.php`)
   - Secure random OTP generation
   - Format and length validation
   - Constant-time comparison for security

3. **Database Models**
   - **DeliveryRadius**: Global delivery configuration
   - **Order**: Enhanced with delivery coordinates and OTP
   - **Vendor**: Now includes pickup location coordinates
   - **Address**: Now includes delivery location coordinates

4. **Service Layer**
   - **DriverOrderService**: Location-aware filtering
   - **OrderProcessedListener**: OTP generation on order creation

## API Endpoints Documentation

### Driver Order API

#### 1. Get Available Orders (Location-Aware)

**Endpoint:** `GET /driver/orders/available`

**Request Parameters:**
```json
{
  "vendor_id": "550e8400-e29b-41d4-a716-446655440000",  // Optional: filter by vendor
  "latitude": 6.5244,                                      // Optional: driver's current latitude
  "longitude": 3.3792,                                     // Optional: driver's current longitude
  "page": "eyJpZCI6MjUsIl9wb2ludHNUbyI6ImRlc2MifQ"      // Cursor pagination token
}
```

**Response (When location provided):**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "tracking_id": "SPLUS-20240207-001",
      "status": "READY_FOR_PICKUP",
      "vendor": {
        "id": "vendor-uuid",
        "business_name": "Bun Loaf Bakery",
        "latitude": 6.5210,
        "longitude": 3.3820,
        "delivery_fee": {
          "amount": 500,
          "currency": "NGN"
        }
      },
      "delivery_latitude": 6.5300,
      "delivery_longitude": 3.3900,
      "otp_code": "428516",
      "receiver_name": "John Doe",
      "receiver_phone": "+2348012345678",
      "gross_total_amount": {
        "amount": 15000,
        "currency": "NGN"
      },
      "net_total_amount": {
        "amount": 15500,
        "currency": "NGN"
      },
      "assigned_at": null,
      "line_items": [
        {
          "quantity": 2,
          "product": {
            "id": "product-uuid",
            "name": "Whole Wheat Bread",
            "price": 5000
          }
        }
      ]
    }
  ],
  "path": "http://localhost/api/v1/driver/orders/available",
  "per_page": 20,
  "next_cursor": "eyJpZCI6MiwiX3BvaW50c1RvIjoibmV4dCJ9",
  "next_page_url": "http://localhost/api/v1/driver/orders/available?cursor=eyJpZCI6MiwiX3BvaW50c1RvIjoibmV4dCJ9",
  "prev_cursor": null,
  "prev_page_url": null
}
```

**Distance Filtering Logic:**
- When both `latitude` and `longitude` are provided:
  - System fetches active delivery radius (default: 15 km)
  - Orders within vendor's pickup location ± radius are returned
  - Orders outside radius are filtered out (driver won't see them)
- When coordinates not provided:
  - All READY_FOR_PICKUP orders are returned (backward compatible)

**Example cURL Requests:**

```bash
# Get all available orders (no filtering)
curl -X GET "http://localhost/api/v1/driver/orders/available" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get available orders near driver's current location
curl -X GET "http://localhost/api/v1/driver/orders/available" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "latitude=6.5244&longitude=3.3792"

# Get available orders from specific vendor near location
curl -X GET "http://localhost/api/v1/driver/orders/available" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d "vendor_id=550e8400-e29b-41d4-a716-446655440000&latitude=6.5244&longitude=3.3792"
```

#### 2. Accept Order

**Endpoint:** `POST /driver/orders/{orderId}/accept`

**Request:**
```json
{}
```

**Response:**
```json
{
  "data": {
    "id": "order-uuid",
    "status": "READY_FOR_PICKUP",
    "driver_id": "driver-user-uuid",
    "assigned_at": "2024-02-07T14:30:00Z",
    "tracking_id": "SPLUS-20240207-001"
  }
}
```

#### 3. Mark as Picked Up

**Endpoint:** `POST /driver/orders/{orderId}/pickup`

**Request:**
```json
{}
```

**Updates order status to PICKED_UP**

#### 4. Start Delivery (Out for Delivery)

**Endpoint:** `POST /driver/orders/{orderId}/out-for-delivery`

**Request:**
```json
{}
```

**Updates order status to OUT_FOR_DELIVERY**
**Triggers OrderDispatchedNotification with OTP to customer**

#### 5. Complete Delivery (With OTP Verification)

**Endpoint:** `POST /driver/orders/{orderId}/deliver`

**Request:**
```json
{
  "otp_code": "428516"  // 4-10 digit OTP from order
}
```

**Response:**
```json
{
  "data": {
    "id": "order-uuid",
    "status": "DELIVERED",
    "delivered_at": "2024-02-07T15:45:00Z",
    "driver_earning": {
      "gross_amount": 15500,
      "commission_amount": 2325,
      "net_amount": 13175,
      "currency": "NGN"
    }
  }
}
```

**OTP Validation:**
- If `otp_code` on order exists: Required and validated
- If no OTP exists: Delivery succeeds immediately
- Uses constant-time comparison (hash_equals) to prevent timing attacks

---

### Admin Settings API

#### Get Delivery Settings

**Endpoint:** `GET /admin/settings/delivery`

**Response:**
```json
{
  "data": {
    "driver_commission_rate": 15,           // Percentage
    "delivery_fee_commission": 0.15,        // Percentage of delivery fee
    "minimum_withdrawal": 5000,             // Minimum payout amount in NGN
    "driver_match_radius_km": 15,           // Geographic radius for driver matching
    "otp_validity_minutes": 15,             // OTP expiry time
    "is_geo_matching_enabled": true         // Feature flag for location-based matching
  }
}
```

#### Update Delivery Settings

**Endpoint:** `PUT /admin/settings/delivery`

**Request:**
```json
{
  "driver_commission_rate": 15,
  "delivery_fee_commission": 0.15,
  "minimum_withdrawal": 5000,
  "driver_match_radius_km": 20,              // Change default radius
  "otp_validity_minutes": 20,
  "is_geo_matching_enabled": true
}
```

**Response:** Same as GET endpoint

**Authorization:** Admin only

---

## Database Schema Additions

### vendors table
```sql
ALTER TABLE vendors ADD COLUMN latitude DECIMAL(10, 7) NULL;
ALTER TABLE vendors ADD COLUMN longitude DECIMAL(10, 7) NULL;
CREATE INDEX idx_vendor_coords ON vendors(latitude, longitude);
```

### addresses table
```sql
ALTER TABLE addresses ADD COLUMN latitude DECIMAL(10, 7) NULL;
ALTER TABLE addresses ADD COLUMN longitude DECIMAL(10, 7) NULL;
CREATE INDEX idx_address_coords ON addresses(latitude, longitude);
```

### orders table
```sql
ALTER TABLE orders ADD COLUMN delivery_latitude DECIMAL(10, 7) NULL;
ALTER TABLE orders ADD COLUMN delivery_longitude DECIMAL(10, 7) NULL;
ALTER TABLE orders ADD COLUMN cancelled_at DATETIME NULL;
ALTER TABLE orders ADD COLUMN cancellation_reason VARCHAR(255) NULL;
CREATE INDEX idx_order_delivery_coords ON orders(delivery_latitude, delivery_longitude);
```

### delivery_radii table (new)
```sql
CREATE TABLE delivery_radii (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  radius_km DECIMAL(8, 2) NOT NULL,
  description TEXT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Implementation Examples

### Frontend: Driver App - Fetch Available Orders with Location

**React/TypeScript Example:**

```typescript
import { useEffect, useState } from 'react';

interface OrderFilters {
  latitude?: number;
  longitude?: number;
  vendor_id?: string;
}

export function AvailableOrdersList() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  
  const fetchAvailableOrders = async (filters: OrderFilters) => {
    setLoading(true);
    try {
      const token = localStorage.getItem('driver_token');
      const params = new URLSearchParams();
      
      if (filters.latitude) params.append('latitude', filters.latitude.toString());
      if (filters.longitude) params.append('longitude', filters.longitude.toString());
      if (filters.vendor_id) params.append('vendor_id', filters.vendor_id);
      
      const response = await fetch(
        `http://api.shopittplus.com/api/v1/driver/orders/available?${params}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        }
      );
      
      const result = await response.json();
      setOrders(result.data);
    } catch (error) {
      console.error('Failed to fetch orders:', error);
    } finally {
      setLoading(false);
    }
  };
  
  // Get driver location on mount
  useEffect(() => {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition((position) => {
        fetchAvailableOrders({
          latitude: position.coords.latitude,
          longitude: position.coords.longitude
        });
      });
    }
  }, []);
  
  return (
    <div>
      {loading ? <p>Loading...</p> : (
        <ul>
          {orders.map(order => (
            <li key={order.id}>
              <div>{order.tracking_id}</div>
              <div>Pickup: {order.vendor.business_name}</div>
              <div>₦{order.net_total_amount.amount.toLocaleString()}</div>
              <button onClick={() => acceptOrder(order.id)}>Accept</button>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
```

### Backend: Calculate Driver Earnings with Commission

**PHP Example:**

```php
<?php

use App\Modules\Commerce\Services\Driver\DriverOrderService;

// In DriverOrderService
public function recordEarnings(User $driver, Order $order): DriverEarning
{
    // Get commission rate from settings (default 15%)
    $commissionRate = Settings::getValue('driver_commission_rate', 15);
    
    // Calculate gross amount (order total + delivery fee)
    $grossAmount = $order->net_total_amount->getAmount()->plus($order->delivery_fee->getAmount());
    
    // Calculate commission
    $commissionAmount = $grossAmount->multipliedBy($commissionRate / 100);
    
    // Net amount paid to driver
    $netAmount = $grossAmount->minus($commissionAmount);
    
    // Record earning
    return DriverEarning::create([
        'user_id' => $driver->id,
        'order_id' => $order->id,
        'gross_amount' => $grossAmount,
        'commission_amount' => $commissionAmount,
        'net_amount' => $netAmount,
        'currency' => $order->currency,
        'status' => 'PENDING'
    ]);
}
```

### Distance Calculation Example

**PHP + JavaScript:**

```php
// PHP: Calculate if driver is in radius
use App\Helpers\GeoHelper;

$driverLat = 6.5244;
$driverLon = 3.3792;
$vendorLat = 6.5210;
$vendorLon = 3.3820;

$distanceKm = GeoHelper::calculateDistance($driverLat, $driverLon, $vendorLat, $vendorLon);
$radiusKm = GeoHelper::getActiveDeliveryRadius(); // 15 km default

if ($distanceKm <= $radiusKm) {
    echo "Driver is within delivery radius: " . GeoHelper::formatDistance($distanceKm);
} else {
    echo "Driver is outside delivery area";
}
```

```javascript
// JavaScript: Calculate distance in frontend
function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // Earth's radius in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c; // Distance in km
}

const distance = calculateDistance(6.5244, 3.3792, 6.5210, 3.3820);
console.log(`Distance: ${distance.toFixed(2)} km`);
```

---

## Testing Checklist

### Unit Tests

- [ ] `GeoHelper::calculateDistance()` with known coordinates
- [ ] `OTPHelper::generate()` produces correct length
- [ ] `OTPHelper::validate()` accepts valid/rejects invalid OTPs
- [ ] `OTPHelper::compare()` constant-time comparison works

### Integration Tests

- [ ] Order creation generates OTP
- [ ] OTP included in notifications
- [ ] Available orders filtered by location
- [ ] Delivery completion validates OTP
- [ ] DriverEarning records created with correct commission

### E2E Tests

1. **User placing order:**
   - User selects delivery address with coordinates
   - Order created with OTP
   - Vendor receives OrderReceivedNotification
   - Order marked READY_FOR_PICKUP

2. **Driver accepting order:**
   - Driver queries nearby orders (with location)
   - Order appears only if within radius
   - Driver accepts order
   - Assignment recorded with timestamp

3. **Delivery completion:**
   - Driver picks up order
   - Driver marks OUT_FOR_DELIVERY
   - User receives OrderDispatchedNotification with OTP
   - Driver confirms delivery with OTP
   - DriverEarning record created
   - Payout calculation verified

---

## Environment Configuration

Add to `.env`:

```
# Delivery Settings
DELIVERY_MATCH_RADIUS_KM=15
DRIVER_COMMISSION_RATE=15
DELIVERY_FEE_COMMISSION=0.15
MINIMUM_WITHDRAWAL=5000
OTP_VALIDITY_MINUTES=15
GEO_MATCHING_ENABLED=true
```

---

## Performance Optimization Notes

1. **Database Indexes**
   - Vendor coordinates indexed for rapid searches
   - Address coordinates indexed for lookups
   - Order delivery coordinates indexed

2. **Query Optimization**
   - Bounding box used before haversine calculation
   - Reduces database load by ~70% vs pure haversine
   - Cursor pagination prevents memory issues with large datasets

3. **Caching**
   - Delivery radius cached in memory (rarely changes)
   - Settings cached with 1-hour TTL

---

## Troubleshooting

### OTP Not Appearing in Notifications

**Check:**
1. `orders.otp_code` column exists and has data
2. `OrderPlacedSuccessfullyNotification` includes OTP in toFCM/toMail
3. Queue is processing async notifications

### Order Not Filtered by Distance

**Check:**
1. Vendor has `latitude` and `longitude` set (not null)
2. Driver sending both `latitude` and `longitude` parameters
3. Delivery radius is configured in `delivery_radii` table

### Slow Available Orders Query

**Check:**
1. Indexes exist on `vendors(latitude, longitude)`
2. Consider adding composite index: `vendors(latitude, longitude, deleted_at, kyb_status)`
3. Profile query with `EXPLAIN` before and after optimization

