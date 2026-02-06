# ShopIT Plus Delivery System - Deployment Checklist

## Pre-Deployment Verification

### Code Files Created ✅

- [x] `app/Helpers/GeoHelper.php` - Distance calculations & radius checking
- [x] `app/Helpers/OTPHelper.php` - OTP generation & validation
- [x] `app/Modules/Commerce/Models/DeliveryRadius.php` - Configuration model
- [x] `app/Console/Commands/SetupDeliveryRadius.php` - CLI setup command
- [x] `tests/Unit/Helpers/GeoHelperTest.php` - Unit tests for geo functions
- [x] `tests/Unit/Helpers/OTPHelperTest.php` - Unit tests for OTP functions
- [x] `tests/Feature/Driver/DriverOrderLocationFilteringTest.php` - Integration tests
- [x] `tests/Feature/Commerce/DriverDeliverOrderOTPTest.php` - OTP delivery tests

### Database Migrations ✅

- [x] `2026_02_07_100000_add_geo_columns_to_vendors_table.php`
- [x] `2026_02_07_100001_add_geo_columns_to_addresses_table.php`
- [x] `2026_02_07_100002_add_geo_and_otp_columns_to_orders_table.php`
- [x] `2026_02_07_100003_create_delivery_radii_table.php`

### Model Updates ✅

- [x] `app/Modules/User/Models/Vendor.php` - Added latitude/longitude casts
- [x] `app/Modules/User/Models/Address.php` - Added latitude/longitude casts
- [x] `app/Modules/Commerce/Models/Order.php` - Added delivery coords & timestamp casts

### Service Layer Updates ✅

- [x] `app/Modules/Commerce/Listeners/OrderProcessedListener.php`
  - Added OTP generation
  - Added delivery coordinate capture
  - Added order placement notification for non-wallet orders
- [x] `app/Modules/Commerce/Services/Driver/DriverOrderService.php`
  - Updated availableOrders() for location filtering
  - Added bounding box optimization

### Notification Updates ✅

- [x] `app/Modules/Commerce/Notifications/OrderPlacedSuccessfullyNotification.php`
  - Added OTP to mail template context
  - Added OTP to FCM data payload
  - Added OTP to database notification
- [x] `app/Modules/Commerce/Notifications/OrderDispatchedNotification.php`
  - Added OTP to mail template context
  - Added OTP to FCM data payload
  - Added OTP to database notification

### Documentation ✅

- [x] `DELIVERY_SYSTEM_DOCUMENTATION.md` - Complete API documentation
- [x] `IMPLEMENTATION_GUIDE.md` - Step-by-step implementation guide
- [x] `IMPLEMENTATION_SUMMARY.md` - Feature summary & architecture
- [x] `DEPLOYMENT_CHECKLIST.md` - This file

---

## Database Setup Verification

### Pre-Migration Checks

```bash
# Check current schema
php artisan tinker
>>> DB::select("SHOW TABLES LIKE 'delivery%';")
>>> DB::select("SHOW COLUMNS FROM vendors;")
>>> DB::select("SHOW COLUMNS FROM addresses;")
>>> DB::select("SHOW COLUMNS FROM orders;")
```

**Expected Result: No delivery_radii table, no lat/lng columns yet**

### Run Migrations

```bash
# Execute all migrations
php artisan migrate

# Or specific migration file
php artisan migrate --path=database/migrations/2026_02_07_100000_add_geo_columns_to_vendors_table.php

# Verify migration status
php artisan migrate:status | grep "2026_02"
```

**Expected Output:**
```
2026_02_07_100000_add_geo_columns_to_vendors_table          YES
2026_02_07_100001_add_geo_columns_to_addresses_table        YES
2026_02_07_100002_add_geo_and_otp_columns_to_orders_table   YES
2026_02_07_100003_create_delivery_radii_table               YES
```

### Post-Migration Verification

```bash
php artisan tinker

# Check vendors table
>>> Schema::hasColumn('vendors', 'latitude')  // true
>>> Schema::hasColumn('vendors', 'longitude') // true

# Check addresses table
>>> Schema::hasColumn('addresses', 'latitude')  // true
>>> Schema::hasColumn('addresses', 'longitude') // true

# Check orders table
>>> Schema::hasColumn('orders', 'delivery_latitude')  // true
>>> Schema::hasColumn('orders', 'delivery_longitude') // true
>>> Schema::hasColumn('orders', 'otp_code')           // true
>>> Schema::hasColumn('orders', 'cancelled_at')       // true

# Check delivery_radii table
>>> DB::table('delivery_radii')->first()
// Should return:
// {
//   "id": "...",
//   "name": "default",
//   "radius_km": 15,
//   "description": "Default delivery radius for driver matching",
//   "is_active": true,
//   "created_at": "2024-02-07...",
//   "updated_at": "2024-02-07..."
// }
```

---

## Code Integration Verification

### Import Statements

```bash
# Verify helper autoloading
php artisan tinker
>>> class_exists('App\Helpers\GeoHelper')        // true
>>> class_exists('App\Helpers\OTPHelper')        // true
```

### Model Casts

```bash
php artisan tinker

# Check Order model
>>> $order = new App\Modules\Commerce\Models\Order();
>>> $order->getCasts()
// Should include:
// 'delivery_latitude' => 'decimal:7',
// 'delivery_longitude' => 'decimal:7',

# Check Vendor model
>>> $vendor = new App\Modules\User\Models\Vendor();
>>> $vendor->getCasts()
// Should include:
// 'latitude' => 'decimal:7',
// 'longitude' => 'decimal:7',
```

### Helper Functions

```bash
php artisan tinker

# Test GeoHelper
>>> use App\Helpers\GeoHelper;
>>> GeoHelper::calculateDistance(6.5244, 3.3792, 6.5210, 3.3820)  // ~3.15 km
>>> GeoHelper::getActiveDeliveryRadius()  // 15.0
>>> GeoHelper::isWithinDeliveryRadius(6.5244, 3.3792, 6.5210, 3.3820, 5.0)  // true

# Test OTPHelper
>>> use App\Helpers\OTPHelper;
>>> $otp = OTPHelper::generate(6);  // "428516"
>>> strlen($otp) === 6  // true
>>> OTPHelper::validate($otp)  // true
>>> OTPHelper::compare($otp, $otp)  // true
```

---

## Unit Test Execution

### Run All New Tests

```bash
# Run unit tests
php artisan test tests/Unit/Helpers/

# Expected Output:
# 2 test files, 16 tests total
# ✓ All passed (100%)

# Run feature tests
php artisan test tests/Feature/Driver/ tests/Feature/Commerce/

# Expected Output:
# 2 test files, 12 tests total
# ✓ All passed (100%)
```

### Individual Test Files

```bash
# Test geographic calculations
php artisan test tests/Unit/Helpers/GeoHelperTest.php
# Expected: 6 tests pass

# Test OTP functionality
php artisan test tests/Unit/Helpers/OTPHelperTest.php
# Expected: 7 tests pass

# Test location filtering
php artisan test tests/Feature/Driver/DriverOrderLocationFilteringTest.php
# Expected: 7 tests pass

# Test OTP delivery verification
php artisan test tests/Feature/Commerce/DriverDeliverOrderOTPTest.php
# Expected: 5 tests pass
```

---

## API Endpoint Verification

### Manual Testing with API

```bash
# 1. Test Get Available Orders (No Location)
curl -X GET "http://localhost/api/v1/driver/orders/available" \
  -H "Authorization: Bearer YOUR_DRIVER_TOKEN"

# Expected: 200 OK, returns array of orders

# 2. Test with Location Filter
curl -X GET "http://localhost/api/v1/driver/orders/available?latitude=6.5244&longitude=3.3792" \
  -H "Authorization: Bearer YOUR_DRIVER_TOKEN"

# Expected: 200 OK, filtered by distance

# 3. Test Accept Order
curl -X POST "http://localhost/api/v1/driver/orders/{ORDER_ID}/accept" \
  -H "Authorization: Bearer YOUR_DRIVER_TOKEN"

# Expected: 200 OK, order assigned to driver

# 4. Test Delivery with OTP
curl -X POST "http://localhost/api/v1/driver/orders/{ORDER_ID}/deliver" \
  -H "Authorization: Bearer YOUR_DRIVER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"otp_code":"428516"}'

# Expected: 200 OK, order delivered, DriverEarning recorded
```

### Postman Collection

```json
{
  "info": {
    "name": "ShopIT Plus Delivery API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Get Available Orders - No Filter",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/v1/driver/orders/available",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{driver_token}}"
          }
        ]
      }
    },
    {
      "name": "Get Available Orders - With Location",
      "request": {
        "method": "GET",
        "url": "{{base_url}}/api/v1/driver/orders/available",
        "header": [
          {
            "key": "Authorization",
            "value": "Bearer {{driver_token}}"
          }
        ],
        "url": {
          "query": [
            {
              "key": "latitude",
              "value": "6.5244"
            },
            {
              "key": "longitude",
              "value": "3.3792"
            }
          ]
        }
      }
    }
  ]
}
```

---

## Configuration Setup

### Environment Variables

Update `.env` file:

```env
# Delivery System Configuration
DELIVERY_MATCH_RADIUS_KM=15
DRIVER_COMMISSION_RATE=15
DELIVERY_FEE_COMMISSION=0.15
MINIMUM_WITHDRAWAL=5000
OTP_VALIDITY_MINUTES=15
GEO_MATCHING_ENABLED=true

# Optional: Google Maps API (for geocoding)
GOOGLE_MAPS_API_KEY=YOUR_API_KEY
```

### Setup Delivery Radius (CLI)

```bash
# View current configuration
php artisan delivery:setup-radius

# Expected Output:
# +-------------------+---------+
# | Setting           | Value   |
# +-------------------+---------+
# | Delivery Radius   | 15 km   |
# | Status            | Active  |
# | Description       | ...     |
# | Updated At        | ...     |
# +-------------------+---------+

# Change radius to 20km
php artisan delivery:setup-radius --radius=20

# Expected Output:
# ✓ Updated default delivery radius to 20 km

# Reset to defaults
php artisan delivery:setup-radius --reset

# Expected Output:
# ✓ Reset delivery configuration to defaults
```

---

## Performance Baseline

### Query Performance Testing

```bash
php artisan tinker

# Test 1: Get available orders (no filter) - should be <100ms
>>> $start = microtime(true);
>>> $orders = DB::table('orders')
                ->whereNull('driver_id')
                ->where('status', 'READY_FOR_PICKUP')
                ->with(['vendor', 'lineItems'])
                ->limit(20)
                ->get();
>>> microtime(true) - $start

# Test 2: Get available orders (with location) - should be <300ms
>>> $start = microtime(true);
>>> // Query with haversine distance calculation
>>> microtime(true) - $start

# Test 3: Index verification
>>> DB::statement("EXPLAIN FORMAT=JSON SELECT * FROM vendors WHERE latitude BETWEEN 6.4 AND 6.6")
// Should show 'using index': true
```

---

## Staging Environment Checklist

### Before Going Live

- [ ] All migrations executed successfully
- [ ] All tests passing (100%)
- [ ] Vendor coordinates populated (minimum 50% of vendors)
- [ ] Address coordinates populated (minimum 50% of addresses)
- [ ] CLI command tested and working
- [ ] OTP notifications tested with sample orders
- [ ] Location filtering tested with multiple drivers
- [ ] Performance benchmarks acceptable (<500ms 95th percentile)
- [ ] Database backups taken
- [ ] Rollback plan documented
- [ ] Support team trained on new features
- [ ] Documentation reviewed and accurate

### Monitoring Setup

```bash
# Enable query logging in .env
APP_DEBUG=false
LOG_LEVEL=info
DB_LOG_QUERIES=true

# Monitor slow queries
tail -f storage/logs/laravel-*.log | grep "slow query\|SELECT.*haversine"

# Check queue status
php artisan queue:work --sleep=3 --tries=3
```

---

## Rollback Plan

### If Issues Occur

```bash
# Reverse migrations
php artisan migrate:rollback

# Or specific migration
php artisan migrate:rollback --step=4

# Verify rollback
php artisan migrate:status | grep "2026_02"
# Should show: YES → Rolled Back

# Clear cache if needed
php artisan cache:clear
php artisan config:cache
```

---

## Post-Deployment Monitoring

### Daily Checks

```bash
# Monitor OTP generation
php artisan tinker
>>> DB::table('orders')->where('otp_code', '!=', null)->count()

# Check delivery radius configuration
>>> App\Modules\Commerce\Models\DeliveryRadius::where('is_active', true)->first()

# Monitor location-based Orders
>>> DB::table('orders')->where('delivery_latitude', '!=', null)->count()

# Check driver earnings
>>> App\Modules\Transaction\Models\DriverEarning::whereDate('created_at', '=', today())->sum('net_amount')
```

### Weekly Reports

```bash
# Orders with geographic data
>>> DB::table('orders')
     ->whereNotNull('delivery_latitude')
     ->whereNotNull('delivery_longitude')
     ->count()

# Driver location tracking updates
>>> DB::table('driver_locations')
     ->whereDate('recorded_at', '>=', now()->subDays(7))
     ->count()

# Average distance to vendor
>>> DB::raw("SELECT AVG(distance) FROM orders")
```

---

## Deployment Sign-Off

**Project:** ShopIT Plus Delivery System with Geo-Fencing & OTP  
**Version:** 1.0.0  
**Date:** February 7, 2024

### Sign-Off Checklist

- [ ] **Developer**: Code review complete
- [ ] **QA**: All tests passing, integration verified
- [ ] **DevOps**: Database migrations prepared, backups taken
- [ ] **Product**: Feature requirements met
- [ ] **Support**: Team trained, documentation reviewed
- [ ] **Security**: OTP comparison uses constant-time, no timing attacks

### Go/No-Go Decision

- [ ] **READY TO DEPLOY** - All checks passed
- [ ] **HOLD** - Issues found, see notes below

**Notes:**

```
(Space for deployment notes/issues)
```

### Deployment Completed

- **Date/Time:** _______________
- **Deployed By:** _______________
- **Environment:** [ ] Staging [ ] Production
- **Version:** _______________
- **Issues Encountered:** _______________
- **Resolution:** _______________

---

## Support Contacts

- **Technical Support:** [Email/Slack Channel]
- **On-Call Engineer:** [Name/Number]
- **Database Admin:** [Name/Slack]
- **DevOps:** [Name/Slack]

---

**Last Updated:** February 7, 2024  
**Next Review:** Post-deployment (48 hours)  

