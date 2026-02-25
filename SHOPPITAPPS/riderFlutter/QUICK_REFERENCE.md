# ShopIT Plus Delivery System - Quick Reference Guide

## 🚀 Quick Start (5 Minutes)

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Setup Delivery Radius
```bash
php artisan delivery:setup-radius --radius=15
```

### 3. Test Installation
```bash
php artisan test tests/Unit/Helpers/ tests/Feature/Driver/ tests/Feature/Commerce/
```

---

## 📁 File Structure

### New Helper Classes
```
app/
├── Helpers/
│   ├── GeoHelper.php          ← Geographic calculations
│   └── OTPHelper.php          ← OTP generation & validation
```

### New Models
```
app/Modules/Commerce/Models/
└── DeliveryRadius.php         ← Configuration for delivery radius
```

### New Commands
```
app/Console/Commands/
└── SetupDeliveryRadius.php    ← CLI for radius setup
```

### Updated Classes
```
app/Modules/Commerce/
├── Listeners/OrderProcessedListener.php        ← OTP generation added
├── Services/Driver/DriverOrderService.php      ← Location filtering added
└── Notifications/
    ├── OrderPlacedSuccessfullyNotification.php ← OTP in notifications
    └── OrderDispatchedNotification.php         ← OTP in notifications

app/Modules/User/Models/
├── Vendor.php                 ← latitude/longitude added
└── Address.php                ← latitude/longitude added

app/Modules/Commerce/Models/
└── Order.php                  ← delivery_latitude/_longitude & otp_code added
```

### Tests
```
tests/
├── Unit/Helpers/
│   ├── GeoHelperTest.php      ← 6 tests
│   └── OTPHelperTest.php      ← 7 tests
└── Feature/
    ├── Driver/DriverOrderLocationFilteringTest.php    ← 7 tests
    └── Commerce/DriverDeliverOrderOTPTest.php         ← 5 tests
```

### Migrations
```
database/migrations/
├── 2026_02_07_100000_add_geo_columns_to_vendors_table.php
├── 2026_02_07_100001_add_geo_columns_to_addresses_table.php
├── 2026_02_07_100002_add_geo_and_otp_columns_to_orders_table.php
└── 2026_02_07_100003_create_delivery_radii_table.php
```

### Documentation
```
└── Root Directory
    ├── DELIVERY_SYSTEM_DOCUMENTATION.md   ← API specifications
    ├── IMPLEMENTATION_GUIDE.md            ← Step-by-step guide
    ├── IMPLEMENTATION_SUMMARY.md          ← Feature overview
    ├── DEPLOYMENT_CHECKLIST.md            ← Pre-deployment checklist
    └── QUICK_REFERENCE.md                 ← This file
```

---

## 🔑 Key Functions

### GeoHelper

```php
use App\Helpers\GeoHelper;

// Calculate distance (Haversine)
$km = GeoHelper::calculateDistance(6.5244, 3.3792, 6.5210, 3.3820);

// Check if within radius
GeoHelper::isWithinDeliveryRadius($dLat, $dLon, $vLat, $vLon, 15);

// Get active radius from DB
$radius = GeoHelper::getActiveDeliveryRadius(); // 15.0 km

// Format for display
echo GeoHelper::formatDistance(15.75); // "15.75 km"

// Optimization: Bounding box
$bbox = GeoHelper::getBoundingBox(6.5244, 3.3792, 15);
```

### OTPHelper

```php
use App\Helpers\OTPHelper;

// Generate OTP
$otp = OTPHelper::generate(6); // "428516"

// Validate
if (OTPHelper::validate($userInput)) { /* OK */ }

// Compare (constant-time)
if (OTPHelper::compare($provided, $stored)) { /* Match */ }

// Constants
OTPHelper::MIN_LENGTH;     // 4
OTPHelper::MAX_LENGTH;     // 10
OTPHelper::DEFAULT_LENGTH; // 6
```

---

## 📡 API Endpoints

### Driver Orders (Location-Aware)

**Get Available Orders**
```
GET /api/v1/driver/orders/available

Query Parameters:
  - latitude (float)    : Driver's latitude (optional)
  - longitude (float)   : Driver's longitude (optional)
  - vendor_id (uuid)    : Filter by vendor (optional)

Response: 200 OK
{
  "data": [
    {
      "id": "order-uuid",
      "tracking_id": "SPLUS-20240207-001",
      "otp_code": "428516",
      "vendor": { "latitude": 6.5210, "longitude": 3.3820, ... },
      "delivery_latitude": 6.5300,
      "delivery_longitude": 3.3900,
      ...
    }
  ],
  "next_cursor": "..."
}
```

**Accept Order**
```
POST /api/v1/driver/orders/{orderId}/accept
Response: 200 OK - sets driver_id, assigned_at
```

**Mark Picked Up**
```
POST /api/v1/driver/orders/{orderId}/pickup
Response: 200 OK - status → PICKED_UP
```

**Start Delivery**
```
POST /api/v1/driver/orders/{orderId}/out-for-delivery
Response: 200 OK - status → OUT_FOR_DELIVERY
```

**Complete Delivery (with OTP)**
```
POST /api/v1/driver/orders/{orderId}/deliver

Body:
{
  "otp_code": "428516"  // 4-10 digits (required if order has OTP)
}

Response: 200 OK
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

---

## 💾 Database Schema

### New Columns

**vendors table**
```sql
ADD COLUMN latitude DECIMAL(10, 7) NULL;
ADD COLUMN longitude DECIMAL(10, 7) NULL;
CREATE INDEX idx_vendor_coords ON vendors(latitude, longitude);
```

**addresses table**
```sql
ADD COLUMN latitude DECIMAL(10, 7) NULL;
ADD COLUMN longitude DECIMAL(10, 7) NULL;
CREATE INDEX idx_address_coords ON addresses(latitude, longitude);
```

**orders table**
```sql
ADD COLUMN delivery_latitude DECIMAL(10, 7) NULL;
ADD COLUMN delivery_longitude DECIMAL(10, 7) NULL;
ADD COLUMN cancelled_at DATETIME NULL;
ADD COLUMN cancellation_reason VARCHAR(255) NULL;
-- otp_code already exists
CREATE INDEX idx_order_delivery_coords ON orders(delivery_latitude, delivery_longitude);
```

**delivery_radii table (NEW)**
```sql
CREATE TABLE delivery_radii (
  id CHAR(36) PRIMARY KEY,
  name VARCHAR(255) UNIQUE NOT NULL,
  radius_km DECIMAL(8, 2) NOT NULL,
  description TEXT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
-- Seeded with: id=uuid, name='default', radius_km=15
```

---

## 🧪 Testing

### Unit Tests (13 tests)

```bash
# All helper tests
php artisan test tests/Unit/Helpers/

# Specific file
php artisan test tests/Unit/Helpers/GeoHelperTest.php  # 6 tests
php artisan test tests/Unit/Helpers/OTPHelperTest.php  # 7 tests
```

### Integration Tests (12 tests)

```bash
# All feature tests
php artisan test tests/Feature/Driver/ tests/Feature/Commerce/

# Specific file
php artisan test tests/Feature/Driver/DriverOrderLocationFilteringTest.php     # 7 tests
php artisan test tests/Feature/Commerce/DriverDeliverOrderOTPTest.php          # 5 tests
```

### Run Everything

```bash
php artisan test --coverage  # Shows coverage stats
```

---

## ⚙️ Configuration Commands

```bash
# View current delivery radius
php artisan delivery:setup-radius

# Set custom radius (e.g., 20km)
php artisan delivery:setup-radius --radius=20

# Reset to defaults (15km)
php artisan delivery:setup-radius --reset
```

---

## 📋 Model Attributes

### Order
```php
$order->otp_code              // "428516" (generated on creation)
$order->delivery_latitude     // 6.5300
$order->delivery_longitude    // 3.3900
$order->cancelled_at          // Timestamp
$order->cancellation_reason   // "Customer request"
```

### Vendor
```php
$vendor->latitude             // 6.5210 (shop location)
$vendor->longitude            // 3.3820
```

### Address
```php
$address->latitude            // 6.5300 (delivery location)
$address->longitude           // 3.3900
```

### DeliveryRadius
```php
$radius = DeliveryRadius::getActiveRadius();
$radius->name                 // "default"
$radius->radius_km            // 15.0
$radius->is_active            // true
$radius->getRadiusInKm()      // 15.0
```

---

## 🔍 Common Queries

### Get Available Orders Near Driver
```php
$order = DB::table('orders')
    ->join('vendors', 'orders.vendor_id', '=', 'vendors.id')
    ->whereNull('orders.driver_id')
    ->where('orders.status', 'READY_FOR_PICKUP')
    ->where(DB::raw("SQRT(POW(69.1 * (vendors.latitude - 6.5244), 2) + POW(69.1 * (3.3792 - vendors.longitude) * COS(vendors.latitude / 57.3), 2))"), '<=', 15)
    ->select('orders.*')
    ->latest('orders.created_at')
    ->cursorPaginate(20);
```

### Check Driver Earnings
```php
$earnings = DriverEarning::where('user_id', $driverId)
    ->where('status', 'PENDING')
    ->sum('net_amount');
```

### Vendor Location Stats
```php
$vendors_with_location = Vendor::whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->count();
```

---

## 🐛 Troubleshooting

### Orders Not Filtered by Distance?
- [ ] Check vendor has latitude/longitude set
- [ ] Verify delivery_radii table has active record
- [ ] Ensure driver sends latitude/longitude in request
- [ ] Check database indexes exist

### OTP Not in Notifications?
- [ ] Verify order.otp_code is populated
- [ ] Check OrderPlacedSuccessfullyNotification updated
- [ ] Ensure queue is running: `php artisan queue:work`
- [ ] Check notification channels in .env

### Tests Failing?
```bash
# Clear cache and retry
php artisan cache:clear
php artisan test --no-coverage

# Run with verbose output
php artisan test --verbose

# Run specific test
php artisan test tests/Unit/Helpers/GeoHelperTest.php --verbose
```

---

## 📞 Key Contact Methods

### Emergency Escalation
1. Check logs: `tail -f storage/logs/laravel-*.log`
2. Check queue status: `php artisan queue:work --sleep=1`
3. Check database: `php artisan tinker`
4. Review migration status: `php artisan migrate:status`

### Documentation Links
- **API Specs:** `DELIVERY_SYSTEM_DOCUMENTATION.md`
- **Setup Guide:** `IMPLEMENTATION_GUIDE.md`
- **Architecture:** `IMPLEMENTATION_SUMMARY.md`
- **Deployment:** `DEPLOYMENT_CHECKLIST.md`

---

## ✅ Pre-Go-Live Checklist

- [ ] Run all migrations successfully
- [ ] Run test suite: 25+ tests passing
- [ ] Setup delivery radius: `php artisan delivery:setup-radius`
- [ ] Populate vendor coordinates (minimum 50%)
- [ ] Populate address coordinates (minimum 50%)
- [ ] Test OTP in notifications
- [ ] Test location filtering with sample drivers
- [ ] Verify API endpoints respond correctly
- [ ] Database backups taken
- [ ] Support team trained
- [ ] Monitoring configured

---

## 🎯 Success Metrics

After deployment, monitor:

```sql
-- Daily active drivers
SELECT COUNT(DISTINCT driver_id) FROM orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Orders using location filtering
SELECT COUNT(*) FROM orders 
WHERE delivery_latitude IS NOT NULL 
AND delivery_longitude IS NOT NULL;

-- OTP usage rate
SELECT 
  COUNT(CASE WHEN otp_code IS NOT NULL THEN 1 END) / COUNT(*) as otp_rate
FROM orders 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);

-- Average delivery time
SELECT AVG(TIMESTAMPDIFF(MINUTE, assigned_at, delivered_at)) 
FROM orders 
WHERE delivered_at IS NOT NULL;

-- Average driver earnings
SELECT AVG(net_amount) FROM driver_earnings 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY);
```

---

**Version:** 1.0.0  
**Last Updated:** February 7, 2024  
**Status:** Production Ready ✅

