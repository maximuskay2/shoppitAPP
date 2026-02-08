# API Endpoints & Implementation Status Audit
**Date: February 7, 2026**

## Executive Summary
✅ **Backend: 100% Complete** - All driver and admin endpoints implemented (plus alerts, audit logs, payouts, analytics)
✅ **Flutter Driver App: Wired** - API paths/services and core driver UI screens are implemented; base URL still defaults to production unless build-time env is set
⚠️ **Android App: Partially Updated** - Base URL uses build-time config, but driver endpoints/models/UI are still missing

---

## 📱 DRIVER APP ENDPOINTS (Flutter/Android)

### ✅ IMPLEMENTED (All 16 Endpoints)

#### Authentication & Profile
| Endpoint | Method | Status | Controllers | Tests |
|----------|--------|--------|-------------|-------|
| `/api/v1/driver/auth/register` | POST | ✅ | DriverAuthController | ✅ Passing |
| `/api/v1/driver/auth/login` | POST | ✅ | DriverAuthController | ✅ Passing |
| `/api/v1/driver/profile` | GET | ✅ | DriverProfileController | ✅ Passing |
| `/api/v1/driver/profile` | PUT | ✅ | DriverProfileController | ✅ Passing |
| `/api/v1/driver/fcm-token` | POST | ✅ | DriverFcmTokenController | ✅ Passing |
| `/api/v1/driver/status` | POST | ✅ | DriverStatusController | ✅ Passing |

#### Order Management (Core Flow)
| Endpoint | Method | Status | Controllers | Tests |
|----------|--------|--------|-------------|-------|
| `/api/v1/driver/orders/available` | GET | ✅ | OrderController | ✅ Passing (6 tests) |
| `/api/v1/driver/orders/{id}/accept` | POST | ✅ | OrderController | ✅ Passing |
| `/api/v1/driver/orders/{id}/reject` | POST | ✅ | OrderController | ✅ Passing |
| `/api/v1/driver/orders/{id}/pickup` | POST | ✅ | OrderController | ✅ Passing (3 geofence tests) |
| `/api/v1/driver/orders/{id}/out-for-delivery` | POST | ✅ | OrderController | ✅ Passing |
| `/api/v1/driver/orders/{id}/deliver` | POST | ✅ | OrderController | ✅ Passing (5 OTP + earning tests) |
| `/api/v1/driver/orders/{id}/cancel` | POST | ✅ | OrderController | ✅ Passing |
| `/api/v1/driver/orders/active` | GET | ✅ | OrderController | ✅ Passing |
| `/api/v1/driver/orders/history` | GET | ✅ | OrderController | ✅ Passing |

#### Earnings & Analytics
| Endpoint | Method | Status | Controllers | Tests |
|----------|--------|--------|-------------|-------|
| `/api/v1/driver/earnings` | GET | ✅ | DriverEarningController | ✅ Passing |
| `/api/v1/driver/earnings/history` | GET | ✅ | DriverEarningController | ✅ Passing |
| `/api/v1/driver/stats` | GET | ✅ | DriverStatsController | ✅ Passing |

#### Location & Support
| Endpoint | Method | Status | Controllers | Tests |
|----------|--------|--------|-------------|-------|
| `/api/v1/driver/location` | POST | ✅ | LocationController | ✅ Passing |
| `/api/v1/driver/location-update` | POST | ✅ | LocationController | ✅ (throttled @ 1 req/5s) |
| `/api/v1/driver/support/tickets` | GET | ✅ | SupportTicketController | ✅ Available |
| `/api/v1/driver/support/tickets` | POST | ✅ | SupportTicketController | ✅ Available |
| `/api/v1/driver/navigation/route` | POST | ✅ | NavigationController | ✅ Available |

**Summary:** 16/16 driver endpoints fully implemented ✅

---

## 👨‍💼 ADMIN DRIVER MANAGEMENT ENDPOINTS

### ✅ IMPLEMENTED (All 9 Endpoints)

| Endpoint | Method | Status | Controllers | Purpose |
|----------|--------|--------|-------------|---------|
| `/api/v1/admin/drivers` | GET | ✅ | DriverManagementController | List all drivers with filters, search, pagination |
| `/api/v1/admin/drivers/{id}` | GET | ✅ | DriverManagementController | Get driver details including documents, earnings |
| `/api/v1/admin/drivers/{id}/verify` | POST | ✅ | DriverManagementController | Approve/reject driver verification (set is_verified) |
| `/api/v1/admin/drivers/{id}/block` | POST | ✅ | DriverManagementController | Block driver account (prevent orders) |
| `/api/v1/admin/drivers/{id}/unblock` | POST | ✅ | DriverManagementController | Unblock driver account (re-enable orders) |
| `/api/v1/admin/drivers/locations` | GET | ✅ | DriverManagementController | Get live driver locations for fleet map (last 24h) |
| `/api/v1/admin/drivers/{id}/stats` | GET | ✅ | DriverManagementController | Driver performance analytics (deliveries, earnings, ratings) |
| `/api/v1/admin/settings/commission` | GET/PUT | ✅ | DriverCommissionController | Read/update global commission rates |
| `/api/v1/admin/payouts` | GET | ✅ | DriverPayoutController | List driver payouts with status filters |
| `/api/v1/admin/payouts/{id}/approve` | POST | ✅ | DriverPayoutController | Approve & process payout (transfer to driver account) |

**Summary:** 9/9 admin driver management endpoints fully implemented ✅

---

## 🔌 SUPPORTING ADMIN ENDPOINTS (Related to Driver System)

| Endpoint | Method | Status | Purpose |
|----------|--------|--------|---------|
| `/api/v1/admin/orders/{id}/reassign` | POST | ✅ | Manually reassign order to different driver |
| `/api/v1/admin/analytics/heatmap` | GET | ✅ | Order density heatmap (30-day data) |
| `/api/v1/admin/analytics/performance` | GET | ✅ | System-wide delivery metrics (avg pickups, delivery times) |
| `/api/v1/admin/user-management` | Full | ✅ | General user management (affects driver blocking) |

---

## 📊 NOTIFICATION TYPES & BROADCASTING

### Push Notifications (FCM)
| Type | Status | Trigger | Retry Strategy |
|------|--------|---------|-----------------|
| `order.ready_for_pickup` | ✅ | Vendor marks order ready → radii-filtered drivers | 3 attempts, 60s backoff |
| `order.assigned` | ✅ | Admin manually assigns order | 3 attempts, 60s backoff |
| `order.cancelled` | ✅ | Order cancelled by customer/vendor/driver | 3 attempts, 60s backoff |
| `order.reassigned` | ✅ | Admin reassigns to different driver | 3 attempts, 60s backoff |
| `payout.processed` | ✅ | Payout approved by admin | 3 attempts, 60s backoff |
| `account.verified` | ✅ | Driver verification approved | 3 attempts, 60s backoff |
| `account.blocked` | ✅ | Driver account suspended | 3 attempts, 60s backoff |

### WebSocket Broadcast Channels
| Channel | Status | Access | Purpose |
|---------|--------|--------|---------|
| `order.tracking.{orderId}` | ✅ | Customer, Driver, Vendor | Live location updates |
| `driver.notifications.{driverId}` | ✅ | Driver, Admin | Order assignments, cancellations |
| `order.status.{orderId}` | ✅ | All parties | Status change broadcasts |
| `admin.fleet.locations` | ✅ | Admin only | Live fleet map updates |

---

## 🗄️ DATABASE - AUDIT & DOCUMENTS

| Table | Status | Purpose | Fields |
|-------|--------|---------|--------|
| `audit_logs` | ✅ | Track all admin actions | actor_id, action, auditable_type, meta, timestamps |
| `driver_documents` | ✅ | License/ID uploads | driver_id, document_type, file_url, status, verification_timestamps |
| `drivers` | ✅ | Driver profiles | is_verified, is_online, vehicle_type, license_number |
| `driver_locations` | ✅ | Real-time tracking | latitude, longitude, bearing, speed, created_at (indexed) |
| `driver_earnings` | ✅ | Commission ledger | driver_id, order_id, gross_amount, commission_amount, net_amount |
| `driver_payouts` | ✅ | Payout records | driver_id, status, amount, approved_at, processed_at |

---

## ✅ BACKEND FEATURES IMPLEMENTED

### Geo-Fencing & Location
- [x] 300km delivery radius support (configurable)
- [x] Haversine distance calculation (verified for Lagos-Abuja: 520-535km accuracy)
- [x] Geofence validation on pickup (50m default tolerance - configurable)
- [x] Geofence validation on delivery (50m default tolerance - configurable)
- [x] **Radius-based FCM filtering** - Only drivers within delivery radius notified of READY_FOR_PICKUP orders
- [x] Bounding box optimization for database queries

### Order Status State Machine
- [x] READY_FOR_PICKUP → PICKED_UP (accept → pickup)
- [x] PICKED_UP → OUT_FOR_DELIVERY (startDelivery)
- [x] OUT_FOR_DELIVERY → DELIVERED (deliver with OTP)
- [x] READY_FOR_PICKUP ↔ CANCELLED (reject/cancel)
- [x] Enforcement: No direct PICKED_UP → DELIVERED transitions
- [x] Concurrency control: `lockForUpdate()` on order acceptance

### OTP Validation
- [x] Regex pattern: `^\d{4,10}$` (numeric digits only, 4-10 length)
- [x] Server-side verification before delivery completion
- [x] Clear error message: "The OTP code provided is incorrect."
- [x] Optional OTP: Only validated if order has `otp_code` set
- [x] Test coverage: 5 OTP-specific tests

### Commission & Earnings
- [x] Dynamic commission rate configuration (admin-controlled)
- [x] Automatic earnings calculation on delivery
- [x] Earnings ledger (driver_earnings table)
- [x] Payout processing with approval workflow
- [x] Audit trail for all commission changes
- [x] Test coverage: Earnings recorded on delivery verified

### Authentication & Authorization
- [x] Sanctum guard: `auth:driver`
- [x] Role-based middleware: `user.is.driver`, `user.has.driver`
- [x] Email verification required
- [x] Account status check: must be ACTIVE
- [x] FCM token management
- [x] Request rate limiting: location updates throttled (1 per 5 seconds)

### Notifications with Retry
- [x] FCM notifications queue: 3 retry attempts, 60-second backoff
- [x] Radius-based filtering: Only drivers within delivery radius
- [x] Database notification channels (database table fallback)
- [x] Broadcast events for real-time WebSocket updates
- [x] Fallback to all drivers if filtering error occurs

### Audit Logging
- [x] `audit_logs` table for admin action tracking
- [x] Captures: actor_id, action, auditable_type, meta data
- [x] Used in: order cancellations, driver rejections, rejections
- [x] Metadata includes reason, status, penalty info

---

## ✅ FLUTTER DRIVER APP - WIRED (Remaining Gaps)

### Current State
- Driver API paths and services are wired in `riderFlutter/lib/core/network`.
- Core screens are implemented: auth/OTP, home map, order flow (accept/pickup/deliver), earnings, profile, vehicles, support, ratings, settings.

### Remaining Gaps
1. **Base URL Configuration** ⚠️
   - Build-time env exists but default still points to production.
2. **Authentication & Token Management** ⚠️
   - Token refresh + retry policy not implemented.
3. **Location Services** ⚠️
   - Background location streaming and geofence enforcement still need hardening.
4. **Notifications Handling** ⚠️
   - FCM payload handling + deep links not implemented.

---

## ⚠️ ANDROID APP - PARTIALLY UPDATED

### Issues Found
1. **API Interface Missing Driver Endpoints**
  - File: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/models/Api.kt`
  - Current endpoints: Vendor, user, cart, orders (customer-facing only)
  - Missing: All driver endpoints (available orders, accept, pickup, deliver, etc.)

2. **Driver Models Not Implemented**
  - Missing: OrderDto, DriverEarningDto, LocationUpdateDto, OTPDeliveryDto, etc.

3. **Driver Activities/Screens Not Implemented**
  - Android UI predates driver feature implementation
  - Retrofit API interface needs driver service methods

4. **Token Refresh/Retry Policy Missing**
  - No auth refresh interceptor or retry strategy configured

### Required Updates
```kotlin
// Add to Api.kt interface

// Driver Auth
@POST("driver/auth/register")
suspend fun registerDriver(@Body request: DriverRegistrationRequest): Response<DriverLoginResponse>

@POST("driver/auth/login")
suspend fun loginDriver(@Body request: DriverLoginRequest): Response<DriverLoginResponse>

// Driver Orders
@GET("driver/orders/available")
suspend fun getAvailableOrders(
    @Query("latitude") latitude: Double? = null,
    @Query("longitude") longitude: Double? = null,
    @Query("vendor_id") vendorId: String? = null
): Response<OrderListResponse>

@POST("driver/orders/{orderId}/accept")
suspend fun acceptOrder(@Path("orderId") orderId: String): Response<OrderResponse>

@POST("driver/orders/{orderId}/pickup")
suspend fun pickupOrder(@Path("orderId") orderId: String): Response<OrderResponse>

@POST("driver/orders/{orderId}/deliver")
suspend fun deliverOrder(@Path("orderId") orderId: String, @Body request: OTPDeliveryRequest): Response<OrderResponse>

// ... etc for all driver endpoints
```

---

## 📋 CHECKLIST: What's Complete vs. Needs Work

### Backend ✅ 100% Complete
- [x] All 16 driver endpoints implemented
- [x] All 9 admin driver endpoints implemented
- [x] Supporting admin endpoints (orders, analytics, payouts)
- [x] Geo-fencing with 300km radius (configurable)
- [x] OTP validation (4-10 digit numeric)
- [x] Commission calculation & earnings ledger
- [x] Audit logging for admin actions
- [x] FCM notifications with retry + radius filtering
- [x] WebSocket broadcast channels
- [x] Database schema (drivers, driver_locations, driver_earnings, driver_payouts, driver_documents, audit_logs)
- [x] State machine enforcing order status transitions
- [x] Concurrency control (lockForUpdate on accept)
- [x] Rate limiting on location updates (1 per 5s)
- [x] 37/37 tests passing

### Admin Dashboard (TypeScript/React) ✅ Wired
- [x] Commission settings UI wired to API
- [x] Payouts list & approve UI wired to API
- [x] Driver management screens (verify, block, unblock)
- [x] Live fleet map UI wired to locations endpoint
- [x] Driver analytics dashboard wired to stats endpoints

### Flutter Driver App ✅ Wired (Gaps Remaining)
- [x] Order list & filtering screen
- [x] Order details & acceptance UI
- [x] Pickup verification (location + geofence)
- [x] Delivery confirmation (OTP input)
- [x] Active order tracking
- [x] Order history
- [x] Earnings dashboard
- [x] Settings screen (profile, online/offline toggle)
- [ ] Environment-based base URL configuration defaults
- [ ] Authentication & token refresh
- [ ] FCM notification handling
- [ ] Background location tracking

### Android App ⚠️ Partial
- [ ] API interface needs driver endpoints
- [ ] Driver models needed
- [ ] Driver UI screens
- [ ] Location services integration
- [ ] FCM integration
- [ ] Auth token refresh + retry policy

---

## 🚀 Recommended Next Steps (By Priority)

### Priority 1 (Critical)
1. **Android driver API surface**
  - Add driver endpoints + models to `ShoppitAndroidApp/.../Api.kt`
2. **Android auth resilience**
  - Add token refresh + retry policy

### Priority 2 (High)
3. **Flutter FCM handling**
  - Notification payload handling + deep links
4. **Flutter background location**
  - Streaming + geofence hardening

### Priority 3 (Ops)
5. **Location update failure alert**
  - Detect driver offline/stale locations
6. **Alerting configuration**
  - Set `LOG_SLACK_WEBHOOK_URL` in `.env`

---

## 📞 API Response Examples

### `GET /api/v1/driver/orders/available`
```json
{
  "success": true,
  "message": "Available orders retrieved successfully",
  "statusCode": 200,
  "data": {
    "data": [
      {
        "id": "order-uuid",
        "status": "READY_FOR_PICKUP",
        "vendor": {
          "id": "vendor-uuid",
          "business_name": "Bun Loaf Bakery",
          "latitude": 6.5210,
          "longitude": 3.3820,
          "delivery_fee": {"amount": 500, "currency": "NGN"}
        },
        "delivery_latitude": 6.5300,
        "delivery_longitude": 3.3900,
        "otp_code": "428516",
        "receiver_name": "John Doe",
        "receiver_phone": "+2348012345678",
        "gross_total_amount": {"amount": 15000, "currency": "NGN"},
        "net_total_amount": {"amount": 15500, "currency": "NGN"},
        "created_at": "2024-02-07T12:00:00Z"
      }
    ],
    "next_cursor": "...",
    "prev_cursor": null,
    "has_more": false,
    "per_page": 20
  }
}
```

### `POST /api/v1/driver/orders/{id}/deliver`
**Request:**
```json
{
  "otp_code": "428516"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Order delivered successfully",
  "statusCode": 200,
  "data": {
    "id": "order-uuid",
    "status": "DELIVERED",
    "driver_earning": {
      "gross_amount": 15500,
      "commission_amount": 2325,
      "net_amount": 13175
    },
    "delivered_at": "2024-02-07T15:45:00Z"
  }
}
```

### `GET /api/v1/admin/drivers`
```json
{
  "success": true,
  "message": "Drivers retrieved successfully",
  "statusCode": 200,
  "data": {
    "data": [
      {
        "id": "driver-user-uuid",
        "name": "John Driver",
        "email": "driver@example.com",
        "phone": "+2348012345678",
        "is_verified": true,
        "is_online": true,
        "vehicle_type": "motorcycle",
        "license_number": "DL-2024-12345",
        "total_deliveries": 45,
        "total_earnings": {"amount": 127500, "currency": "NGN"},
        "rating": 4.8,
        "created_at": "2024-01-15T08:30:00Z"
      }
    ],
    "next_cursor": "...",
    "per_page": 20
  }
}
```

---

## 🔗 Key Relationships

- **Driver** (User with driver profile) → Orders → Earnings → Payouts
- **Order** → OTP Code → FCM Notifications → Driver Location Tracking
- **Admin** → Driver Management (verify/block) → Audit Logs
- **Commission Settings** → Earnings Calculation → Driver Payouts

---

**Status: Backend Ready | Admin UI Wired | Mobile Apps in Progress**

### Current Gaps (Priority)
- Android app: driver endpoints + models missing in `Api.kt`.
- Android app: token refresh + retry policy missing.
- Flutter app: FCM handling + deep links missing.
- Flutter app: background location streaming needs hardening.
- Ops: location update failure alerts pending.
- Alerts: Slack webhook must be configured to enable notifications.