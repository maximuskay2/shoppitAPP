# Driver App Integration Roadmap (Laravel Backend)

## ⚠️ API IMPLEMENTATION STATUS AUDIT

### Critical Driver Endpoints (REQUIRED)
| Endpoint | Method | Status | Priority | Description |
|----------|--------|--------|----------|-------------|
| `/api/v1/driver/auth/login` | POST | ✅ Implemented | P0 | Driver authentication |
| `/api/v1/driver/auth/register` | POST | ✅ Implemented | P0 | Driver registration with documents |
| `/api/v1/driver/profile` | GET/PUT | ✅ Implemented | P0 | Driver profile & vehicle details |
| `/api/v1/driver/status` | POST | ✅ Implemented | P0 | Toggle online/offline status |
| `/api/v1/driver/orders/available` | GET | ✅ Implemented | P0 | List orders ready for pickup (with radius filter) |
| `/api/v1/driver/orders/{id}/accept` | POST | ✅ Implemented | P0 | Accept order assignment |
| `/api/v1/driver/orders/{id}/reject` | POST | ✅ Implemented | P1 | Reject order with reason |
| `/api/v1/driver/orders/{id}/pickup` | POST | ✅ Implemented | P0 | Mark order picked up (300km geo-fence enforced) |
| `/api/v1/driver/orders/{id}/out-for-delivery` | POST | ✅ Implemented | P0 | Mark order out for delivery |
| `/api/v1/driver/orders/{id}/deliver` | POST | ✅ Implemented | P0 | Complete delivery with OTP + 300km geo-fence |
| `/api/v1/driver/orders/active` | GET | ✅ Implemented | P0 | Current assigned/in-progress order |
| `/api/v1/driver/orders/history` | GET | ✅ Implemented | P1 | Completed deliveries with pagination |
| `/api/v1/driver/location` | POST | ✅ Implemented | P0 | Realtime location updates with throttling + broadcast |
| `/api/v1/driver/earnings` | GET | ✅ Implemented | P1 | Earnings summary & breakdown |
| `/api/v1/driver/earnings/history` | GET | ✅ Implemented | P2 | Detailed payout history |
| `/api/v1/driver/fcm-token` | POST | ✅ Implemented | P0 | Register FCM token for notifications |

### Admin Driver Management Endpoints (REQUIRED)
| Endpoint | Method | Status | Priority | Description |
|----------|--------|--------|----------|-------------|
| `/api/v1/admin/drivers` | GET | ✅ Implemented | P0 | List all drivers with filters |
| `/api/v1/admin/drivers/{id}` | GET | ✅ Implemented | P0 | Get driver details & documents |
| `/api/v1/admin/drivers/{id}/verify` | POST | ✅ Implemented | P0 | Approve/reject driver verification |
| `/api/v1/admin/drivers/{id}/block` | POST | ✅ Implemented | P1 | Block driver account |
| `/api/v1/admin/drivers/{id}/unblock` | POST | ✅ Implemented | P1 | Unblock driver account |
| `/api/v1/admin/drivers/locations` | GET | ✅ Implemented | P0 | Live locations for fleet map |
| `/api/v1/admin/orders/{id}/reassign` | POST | ✅ Implemented | P1 | Manual driver reassignment |
| `/api/v1/admin/payouts` | GET | ✅ Implemented | P0 | List driver payouts |
| `/api/v1/admin/payouts/{id}/approve` | POST | ✅ Implemented | P0 | Approve & process payout |
| `/api/v1/admin/settings/commission` | GET/PUT | ✅ Implemented | P0 | Commission rate configuration |

### Supporting Endpoints (Enhancement)
| Endpoint | Method | Status | Priority | Description |
|----------|--------|--------|----------|-------------|
| `/api/v1/driver/support/tickets` | GET/POST | ✅ Implemented | P2 | Driver support tickets |
| `/api/v1/driver/navigation/route` | POST | ✅ Implemented | P2 | Get optimized route (straight-line estimate) |
| `/api/v1/driver/orders/{id}/cancel` | POST | ✅ Implemented | P1 | Cancel accepted order (with penalty) |
| `/api/v1/driver/stats` | GET | ✅ Implemented | P2 | Performance metrics dashboard |
| `/api/v1/admin/drivers/{id}/stats` | GET | ✅ Implemented | P2 | Driver performance analytics |
| `/api/v1/admin/analytics/heatmap` | GET | ✅ Implemented | P2 | Order density heatmap data |
| `/api/v1/admin/analytics/performance` | GET | ✅ Implemented | P2 | System-wide delivery metrics |

### WebSocket/Broadcasting Channels (REQUIRED)
| Channel | Status | Priority | Description |
|---------|--------|----------|-------------|
| `order.tracking.{order_id}` | ✅ Implemented | P0 | Live driver location to customer |
| `driver.notifications.{driver_id}` | ✅ Implemented | P0 | Realtime order assignments |
| `admin.fleet.locations` | ✅ Implemented | P1 | Live fleet map updates |
| `order.status.{order_id}` | ✅ Implemented | P1 | Order status changes broadcast |

### Database Schema Gaps (CRITICAL)
| Table/Column | Status | Priority | Description |
|--------------|--------|----------|-------------|
| `drivers` table | ✅ Implemented | P0 | Driver profile/verification data |
| `users.vehicle_type` | ⚠️ Partial | P0 | Implemented as `drivers.vehicle_type` |
| `users.license_number` | ⚠️ Partial | P0 | Implemented as `drivers.license_number` |
| `users.is_verified` | ⚠️ Partial | P0 | Implemented as `drivers.is_verified` |
| `users.is_online` | ⚠️ Partial | P0 | Implemented as `drivers.is_online` |
| `users.fcm_token` | ⚠️ Partial | P0 | Implemented via `device_tokens` table (no users column) |
| `orders.driver_id` | ✅ Implemented | P0 | Driver assignment FK |
| `orders.assigned_at` | ✅ Implemented | P0 | Assignment timestamp |
| `orders.picked_up_at` | ✅ Implemented | P0 | Pickup timestamp |
| `orders.delivered_at` | ✅ Implemented | P0 | Delivery timestamp |
| `orders.otp_code` | ✅ Implemented | P0 | Delivery verification OTP |
| `driver_locations` table | ✅ Implemented | P0 | Realtime location tracking |
| `driver_earnings` table | ✅ Implemented | P0 | Earnings & commission ledger |
| `driver_payouts` table | ✅ Implemented | P0 | Payout records |
| `driver_documents` table | ✅ Implemented | P1 | Uploaded verification docs |
| `audit_logs` table | ✅ Implemented | P1 | Admin action audit trail |

### Notification Types (FCM) (REQUIRED)
| Notification Type | Status | Priority | Trigger |
|-------------------|--------|----------|---------|
| `order.ready_for_pickup` | ✅ Implemented | P0 | Vendor marks order ready |
| `order.assigned` | ✅ Implemented | P0 | Admin manually assigns order |
| `order.cancelled` | ✅ Implemented | P0 | Order cancelled by customer/vendor |
| `order.reassigned` | ✅ Implemented | P1 | Admin reassigns to different driver |
| `payout.processed` | ✅ Implemented | P2 | Earnings paid out |
| `account.verified` | ✅ Implemented | P1 | Driver verification approved |
| `account.blocked` | ✅ Implemented | P1 | Account suspended |

### Business Logic Requirements (MISSING VALIDATION)
- [x] **Concurrency Control**: `lockForUpdate()` on order acceptance to prevent double assignment
- [x] **Geo-Fencing**: 300km radius validation for pickup/delivery actions
- [x] **OTP Generation**: 6-digit OTP on order placement
- [x] **OTP Validation**: Server-side verification before delivery completion
- [x] **Commission Calculation**: Dynamic commission rate from settings
- [x] **Earnings Ledger**: Transaction log for driver payouts
- [x] **Status State Machine**: Centralized validation for status transitions
- [x] **Distance Calculation**: Haversine formula for driver-to-vendor and vendor-to-customer distances
- [x] **Driver Radius Matching**: Only notify drivers within X km of pickup location
- [x] **Rate Limiting**: Throttle location updates (max 1 per 5 seconds)
- [x] **Auth Middleware**: `auth:driver` guard added for driver API

### Frontend Integration Gaps
- [x] **Flutter Driver App**: Scaffold created in `riderFlutter` (package `rider_flutter`)
- [x] **Admin Dashboard**: Payouts UI wired to API (approve + history)
- [x] **Admin Dashboard**: Commission settings wired to backend
- [x] **Admin Dashboard**: Driver management screens wired to API (verify/block)
- [x] **Admin Dashboard**: Live fleet map list wired to locations API

---

## 🚨 IMMEDIATE ACTION ITEMS (Sprint 1 - Week 1)

### Day 1-2: Core Infrastructure (BLOCKERS)
1. **Migration: Driver Fields** (BE-01) - ⚠️ Partial
   ```bash
   php artisan make:migration add_driver_fields_to_users_table
   ```
   - Implemented as `drivers` table with `vehicle_type`, `license_number`, `is_verified`, `is_online`
   - Missing: `users.fcm_token`, `latitude`, `longitude` columns
   - Test: Verify driver record exists and is linked to user

2. **Migration: Order Driver Linkage** (BE-02) - ✅ Done
   ```bash
   php artisan make:migration add_driver_fields_to_orders_table
   ```
   - Add: `driver_id` (FK), `assigned_at`, `picked_up_at`, `delivered_at`, `otp_code`
   - Test: Create order with driver assignment

3. **Migration: Driver Locations** (BE-03) - ✅ Done
   ```bash
   php artisan make:migration create_driver_locations_table
   ```
   - Schema: `id`, `user_id`, `latitude`, `longitude`, `bearing`, `speed`, `accuracy`, `created_at`
   - Index: `(user_id, created_at)` for fast queries
   - Test: Insert 1000 location records and query last 10 for a driver

### Day 3-4: Authentication & Core Endpoints (CRITICAL)
4. **Driver Authentication** (BE-04) - ✅ Done
   ```bash
   php artisan make:controller Api/V1/Driver/AuthController
   php artisan make:request Driver/LoginRequest
   php artisan make:request Driver/RegisterRequest
   ```
   - Routes: `POST /driver/auth/login`, `POST /driver/auth/register`
   - Validation: Require license_number, vehicle_type on registration
   - Response: JWT token with driver profile
   - Test: Register driver → Login → Get profile with auth token

5. **Driver Orders Endpoints** (BE-04, BE-05) - ✅ Done
   ```bash
   php artisan make:controller Api/V1/Driver/OrderController
   ```
   - Implemented: available, accept, reject, pickup, out-for-delivery, deliver
   - Implemented: active, history, radius filter, geo-fence, earnings calc
   - `POST /driver/orders/{id}/accept`: Uses `DB::transaction()` + `lockForUpdate()`; status not updated to ASSIGNED yet
   - Test: Full flow from available → accept → pickup → out-for-delivery → deliver

### Day 5: Location Tracking & Notifications (HIGH)
6. **Location Updates** (BE-04, BE-07) - ✅ Done
   ```bash
   php artisan make:controller Api/V1/Driver/LocationController
   ```
   - Route: `POST /driver/location` and `/driver/location-update`
   - Rate limit + broadcast enabled
   - Test: Send 20 location updates, verify rate limiting kicks in

7. **FCM Notifications** (BE-06) - ✅ Done
   ```bash
   composer require kreait/laravel-firebase
   php artisan make:notification Driver/OrderReadyNotification
   ```
   - Notification: `order.ready_for_pickup`, `order.assigned`, `order.cancelled`
   - Logic: Query all online drivers within radius, send FCM push
   - Test: Mark order ready → Verify driver receives notification

### Week 2: Admin & Financial Control
8. **Admin Driver Endpoints** (BE-09, BE-10) - ✅ Done
   - `GET /admin/drivers`: Paginated list with search/filters
   - `POST /admin/drivers/{id}/verify`: Set `is_verified=true`
   - `POST /admin/drivers/{id}/block`: Set `is_blocked=true`, send notification
   - Test: Admin approves driver → Driver can accept orders

9. **Earnings & Payouts** (BE-08, BE-10) - ✅ Done
   ```bash
   php artisan make:migration create_driver_earnings_table
   php artisan make:migration create_driver_payouts_table
   ```
   - Earnings ledger: Record commission on each delivery
   - Payout API: `GET /admin/payouts`, `POST /admin/payouts/{id}/approve`
   - Test: Complete 5 deliveries → Check earnings → Process payout

---

## 📋 VERIFICATION CHECKLIST (Before Mobile Development)

### ✅ Phase 1: Database & Models (Day 1-2)
- [ ] Run all migrations without errors
- [ ] Seed 10 driver users with different vehicle types
- [ ] Seed 20 orders, 5 with `READY_FOR_PICKUP` status
- [ ] Insert 100 location records for 3 drivers
- [ ] Verify foreign keys enforce referential integrity

### ✅ Phase 2: Authentication (Day 3)
- [ ] Driver can register with license_number and vehicle_type
- [ ] Driver receives JWT token on login
- [ ] Invalid credentials return 401
- [ ] Driver profile endpoint returns correct data
- [ ] Postman collection exported with auth examples

### ✅ Phase 3: Order Flow (Day 4)
- [ ] Available orders filtered by 10km radius and status
- [ ] Two drivers cannot accept same order (concurrency test)
- [ ] Pickup action fails if driver is 301km away from vendor
- [ ] Delivery action fails with wrong OTP
- [ ] Order status transitions follow state machine rules

### ✅ Phase 4: Realtime (Day 5)
- [ ] Location updates stored with timestamp
- [ ] WebSocket broadcasts location to subscribed clients
- [ ] Rate limiting prevents spam (test with 30 rapid requests)
- [ ] FCM notification received on device when order ready

### ✅ Phase 5: Admin Control (Week 2)
- [ ] Admin can view all drivers with filtering
- [ ] Admin verification sets `is_verified=true`
- [ ] Blocked driver cannot accept orders (403 response)
- [ ] Earnings calculated correctly after delivery
- [ ] Payout approval resets driver balance

---

## 🧪 TESTING STRATEGY

### Unit Tests (Target: 80% coverage)
```bash
php artisan make:test Driver/OrderAcceptanceTest
php artisan make:test Driver/LocationUpdateTest
php artisan make:test Admin/DriverVerificationTest
```

**Critical Test Cases:**
1. **Concurrency:** 3 drivers simultaneously accept same order → Only 1 succeeds
2. **Geo-Fence:** Driver 301km away from vendor → Pickup fails with validation error
3. **OTP Validation:** Wrong OTP → Delivery fails, correct OTP → Success
4. **Commission Calculation:** Order total $100, commission 15% → Driver earns $15
5. **Rate Limiting:** 20 location updates in 10 seconds → Throttled after 12

### Integration Tests
```bash
php artisan make:test Feature/DriverDeliveryFlowTest
```

**Full Flow Test:**
1. Vendor marks order `READY_FOR_PICKUP`
2. System sends FCM to 3 online drivers within 10km
3. Driver A accepts order
4. Driver B tries to accept → Gets 409 Conflict
5. Driver A marks picked up (at vendor location)
6. Driver A updates location 5 times
7. Customer tracks order on map
8. Driver A delivers with correct OTP
9. System calculates earnings
10. Admin approves payout

### Load Testing (Apache Bench / K6)
```bash
ab -n 1000 -c 50 -H "Authorization: Bearer TOKEN" \
   -p location.json \
   http://localhost/api/v1/driver/location
```

**Target Metrics:**
- Location updates: 1000 req/sec @ p95 < 50ms
- Order acceptance: 100 req/sec with 0 double assignments
- Available orders: 200 req/sec @ p95 < 100ms

---

## 🚀 DEPLOYMENT CHECKLIST

### Environment Setup
- [ ] FCM credentials added to `.env` (FIREBASE_CREDENTIALS)
- [ ] Pusher/Reverb configured for WebSockets
- [x] Queue worker notes + checks added
- [x] Scheduler cron notes + checks added
- [x] Redis cache notes + checks added

### Monitoring & Alerts
- [x] Structured request logging with request id + actor context
- [x] Alert hook for 5xx responses (Slack)
- [ ] Log driver actions to `driver_audit_logs`
- [x] Alert on failed FCM pushes (>5% failure rate)
- [x] Alert on stuck orders (READY for >15 minutes)
- [x] Alert on dispatch/payout failures
- [ ] Alert on location update failures (driver app offline)
- [ ] Monitor earnings calculation accuracy (weekly reconciliation)

### Security
- [ ] Rate limiting on all driver endpoints
- [ ] CORS configured for mobile app domains
- [ ] API keys rotated (FCM, Maps, Payment gateway)
- [ ] Driver location data retention policy (30 days)
- [ ] PII encryption for license_number field

---

## 📱 ANDROID APP INTEGRATION (Post-Backend Completion)

### Prerequisites Checklist
- [ ] All P0 endpoints implemented and tested
- [ ] Postman collection with example requests
- [ ] Swagger/OpenAPI docs generated
- [ ] Staging environment with seed data
- [ ] FCM project configured with Android app registration

### Required Android Libraries
```gradle
// Retrofit for API
implementation 'com.squareup.retrofit2:retrofit:2.9.0'
implementation 'com.squareup.retrofit2:converter-gson:2.9.0'

// FCM for notifications
implementation 'com.google.firebase:firebase-messaging:23.0.0'

// Google Maps for tracking
implementation 'com.google.android.gms:play-services-maps:18.1.0'
implementation 'com.google.android.gms:play-services-location:21.0.1'

// WebSocket for realtime
implementation 'com.pusher:pusher-java-client:2.4.0'
```

### Environment-Based Base URL Config
```kotlin
// BuildConfig setup in build.gradle
buildTypes {
    debug {
        buildConfigField "String", "BASE_URL", "\"http://10.0.2.2:8000/api/v1/\""
    }
    staging {
        buildConfigField "String", "BASE_URL", "\"https://staging.shopittplus.org/api/v1/\""
    }
    release {
        buildConfigField "String", "BASE_URL", "\"https://shopittplus.espays.org/api/v1/\""
    }
}
```

---

## 1) The Audit: What to check in the existing app/API
- **Order Status Schema:** Confirm `orders` supports status values for:
	- `READY_FOR_PICKUP` (Triggered by Vendor)
	- `PICKED_UP` (Triggered by Driver)
	- `OUT_FOR_DELIVERY` (Triggered by Driver)
	- `DELIVERED` (Final state)
- **Location Tracking Architecture:** Verify `driver_locations` table (or equivalent) exists and check for WebSocket support (Reverb/Pusher/Soketi/Firebase).
- **Notification Dispatcher:** Review notifications to ensure they can target roles (Driver vs Customer) and online drivers only.
- **Authentication & Permissions:** Confirm auth supports a `Driver` role without leaking Vendor/Admin access.
- Output: gap list + migration plan.

## 2) Core implementation for the Driver app (backend support)
### A) Incoming request loop
- **Push notifications:** FCM high-priority ping when order status changes to ready/accepted.
- **Accept/reject logic:** Include pickup & drop-off distance and estimated earning fields in the payload.

### B) Navigation & tracking
- **Maps phases:**
	1. Driver → Vendor (pickup)
	2. Vendor → Customer (delivery)
- **Background location updates:** Streaming coordinates even when device is locked.

### C) Perfect handover logic
- **Pickup verification:** Enable “Picked Up” only within 300km geo-fence of Vendor.
- **Delivery proof:** Support one or more of:
	- OTP verification
	- In-app photo
	- Digital signature

## 3) Database & shared model strategy
- Extend `users` with `vehicle_type`, `license_number`, `is_online` (or `is_active`) and `fcm_token`.
- Add `driver_id` FK to `orders`, plus `otp_code` if not present.
- Add `driver_locations` table: `user_id`, `lat`, `lng`, `bearing`, `timestamp` (indexed).
- **Concurrency:** Use transactions + `lockForUpdate()` on accept; fallback to optimistic checks for duplicate accepts.

## 4) Driver API endpoints
- `GET /driver/orders/available`: orders with `status == READY_FOR_PICKUP` and `driver_id` is null.
- `POST /driver/orders/{id}/accept`: assign driver, status → `PICKED_UP` or `ASSIGNED` (define final enum).
- `POST /driver/orders/{id}/delivered`: OTP check before status → `DELIVERED`.
- `POST /driver/location-update`: low-latency coordinate updates.

## 5) Order payload requirements
- Return vendor pickup address and customer delivery address in a nested structure for driver usage.

## 6) Realtime & notifications
- Broadcast channel `order.tracking.{order_id}` for live location.
- FCM notification to online drivers on `READY_FOR_PICKUP`.

## 7) Delivery flow (perfect flow)
1. Vendor sets status to `READY_FOR_PICKUP`.
2. Backend notifies online drivers via FCM.
3. Driver accepts (status → `ASSIGNED` or `PICKED_UP`).
4. Driver marks picked up (status → `OUT_FOR_DELIVERY`).
5. Backend notifies customer “On the way”.
6. Driver delivers + OTP (status → `DELIVERED`).
7. Backend updates vendor balance and driver earnings.

## 8) Suggested tech alignment (Flutter)
- Use existing state-management pattern (GetX/Provider/Bloc) for consistency.
- Use WebSockets for live map tracking.

## 9) Seed/test data
- Seed driver users, sample orders with OTP, and location logs.
- Provide local verification steps.

---

# Admin Dashboard Implementation (Governance, Oversight, Financial Control)

> Scope: Admin dashboard at /Applications/XAMPP/xamppfiles/htdocs/shopittplus-api/admin must govern and control the driver app. Since the backend is Laravel, build these views using **Laravel Nova**, **Filament**, or a **custom Blade/Vue dashboard**.

## 1) Driver Lifecycle & Compliance (Gatekeeper)
- **Instruction:** Implement a **Driver Verification Pipeline**. When a driver registers, their account remains `inactive` by default. Admin reviews uploaded documents (License, ID, Insurance). Only upon approval should `is_verified` be set to `true`, enabling the driver to go online.
- **Feature:** Document viewer with **Approve** / **Reject** (with reason) actions.

## 2) Live Dispatch (God View)
- **Instruction:** Build a **Global Fleet Map** with Leaflet or Google Maps. Pull latest coordinates from `driver_locations` and render driver markers.
- **State Colors:**
	- Online (Idle)
	- Busy (In-Delivery)
	- Offline
- **Logic:** Update live using **30-second polling** or **WebSockets** without full page refresh.

## 3) Order Intervention & Manual Override
- **Instruction:** Implement **Manual Order Re-assignment**. Admin can select an active order and change the `driver_id`.
- **Logic:**
	- Notify old driver: **Cancellation**
	- Notify new driver: **New Assignment**
	- Log each change in `audit_logs` for accountability.

## 4) Financial & Payout Management
- **Instruction:** Develop a **Commission & Wallet Engine**.
- **Global Settings:** Set delivery fee split percentage.
- **Ledger View:** Table with each driver's **Current Balance** (total earned minus payouts).
- **Payout Trigger:** Action to mark payout as **Paid** and reset pending balance to zero.
- **Logic:** Use `DB::transaction` to update balance and insert payout record atomically.

## 5) Performance Analytics & Heatmaps
- **Instruction:** Create a **Heatmap Analytics** view using order `latitude` and `longitude` from the last 30 days to identify demand density.
- **Metrics:**
	- Average Pickup Time (Vendor -> Driver)
	- Average Delivery Time (Driver -> Customer)
	- Driver Cancellation Rate

## 6) Proactive Conflict Resolution
- **Instruction:** Create a **System Health Monitor** with a **Flagged Orders** list.
- **Rule:** Orders in `READY` status for more than 15 minutes without acceptance should appear here.
- **UX:** Trigger a visual/audio alert for Admin to take action.

## Summary Checklist for Admin Developer
- [x] **CRUD for Drivers:** Edit profile, block/unblock, verify documents.
- [x] **Live Map:** Integration with `driver_locations` via WebSockets.
- [x] **Finance Module:** Commission settings and payout history.
- [x] **Audit Logs:** Track which Admin changed which order status or assigned which driver.

---

# Production Readiness Additions (Findings + Roadmap Tasks)

## A) Gaps observed in the current codebase
- **Driver module endpoints** implemented for reject/active/history, geo-fence, and radius filter.
- **Driver documents + audit logs** are now implemented.
- **Admin payouts UI** wired to real APIs.
- **Commission settings UI** wired to backend settings API.
- ✅ **Android app base URL** now uses build-time configs (debug/release).

## B) Backend tasks to close production gaps
### 1) Driver domain + data model
- ✅ `drivers` table added with vehicle/license/verification fields; `fcm_token` uses `device_tokens` table.
- ✅ `driver_id`, `assigned_at`, `picked_up_at`, `delivered_at` on `orders`.
- ✅ `driver_locations` table with indexes.
- ✅ `driver_documents` and `audit_logs` implemented.

### 2) Driver API surface
- ✅ Implemented `available`, `accept`, `pickup`, `out-for-delivery`, `deliver`, `location`, `auth`, `profile`, `status`, `fcm-token`.
- ✅ `lockForUpdate()` on accept to prevent double assignment.
- ✅ Implemented: reject, active, history, geo-fence, radius filter.

### 3) Notifications + realtime
- ✅ Driver-targeted FCM notifications added for ready/assigned/cancelled/reassigned/verified/blocked/payout.
- ✅ Broadcast channels for order tracking, driver notifications, order status, and admin fleet updates.
- ✅ Radius-based driver selection implemented.
- ✅ Retry strategy for failed notifications.

### 4) Payouts and commission control
- ✅ `driver_earnings` + `driver_payouts` tables and models.
- ✅ Admin endpoints to read/update commission rate.
- ✅ Admin payout endpoints: list, approve.
- ✅ Export/reconcile flows implemented.

### 5) Audit logging
- ✅ `audit_logs` table added.
- ✅ Middleware captures admin order overrides, payouts, and driver status changes.

## C) Admin web tasks to move from mock to live
- ✅ **Delivery -> Payouts** wired to real APIs (list, filter, mark paid).
- ✅ **Commission Settings** wired to backend config with validation.
- ✅ **Driver Management** screens fed by driver APIs (verification, status, documents, block/unblock).

## D) Android app production readiness
- ✅ Replace hardcoded Retrofit base URL with build-time environment config (dev/staging/prod).
- Add auth token refresh interceptor and network retry policy for flaky mobile networks.
- Add driver endpoints/models to `Api.kt` (current app is user/vendor only).
- Add delivery status actions once driver endpoints exist.

## E) Operational hardening
- ✅ Add queue/scheduler/Redis setup notes + checks.
- ✅ Add API rate limits for driver accept/location/status/payout endpoints.
- ✅ Add structured logs + alert hooks for server errors.
- ✅ Alerts for dispatch failures, payout failures, and stuck orders.
- ⚠️ Pending: alert on location update failures (driver app offline).

### Ops Setup Notes (Quick Checks)
- **Queue:** set `QUEUE_CONNECTION=database` or `redis`, run `php artisan queue:work --queue=default,notifications --tries=3`.
- **Queue tables:** `php artisan queue:table && php artisan queue:failed-table && php artisan migrate` (if using database queue).
- **Scheduler:** add cron `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`.
- **Redis cache:** set `CACHE_STORE=redis`, `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`.
- **Health checks:** verify `php artisan queue:failed` is empty and `php artisan schedule:list` shows jobs.

---

# Ticketized Roadmap (Grouped by Backend/Admin/Mobile)

## Backend Tickets
- ✅ **BE-01 Driver data model**: Add driver fields to `users` or a `drivers` table; include `vehicle_type`, `license_number`, `is_verified`, `is_online`, `fcm_token`.
- ✅ **BE-02 Orders driver linkage**: Add `driver_id`, `assigned_at`, `picked_up_at`, `delivered_at` to `orders` and migrate existing data.
- ✅ **BE-03 Driver locations store**: Create `driver_locations` table with indexes on `user_id`, `created_at`, and geospatial fields.
- ✅ **BE-04 Driver API endpoints**: Implement `available`, `accept`, `pickup`, `deliver`, and `location-update` endpoints with auth + validation.
- ✅ **BE-05 Concurrency guard**: Use `lockForUpdate()` and status checks to prevent double assignment.
- ✅ **BE-06 Driver notifications**: Add FCM push for `READY_FOR_PICKUP`, cancellation, and reassignment; include retry strategy.
- ✅ **BE-07 Realtime tracking**: Broadcast channels for order tracking and driver online status.
- ✅ **BE-08 Payout ledger**: Add `driver_payouts` table; compute earnings and payout history.
- ✅ **BE-09 Commission settings API**: Persist commission rates; expose admin endpoints to read/update with validation.
- ✅ **BE-10 Admin payout API**: List, approve, reconcile payouts; export CSV.
- ✅ **BE-11 Audit logging**: Create `audit_logs` table and middleware for admin actions.
- ⚠️ **BE-12 Ops hardening**: Queue workers, rate limits, structured logs, and alerts for dispatch/payout failures.

## Admin Web Tickets
- ✅ **AD-01 Driver verification UI**: Document review with approve/reject + reason.
- ✅ **AD-02 Driver management UI**: CRUD, block/unblock, status toggles, and profile edits.
- ✅ **AD-03 Live fleet map**: Map with polling or WebSockets and driver state colors.
- ✅ **AD-04 Order override UI**: Reassign driver, notify both drivers, write audit log.
- ✅ **AD-05 Payouts UI**: Replace mock payout data with API list/approve/export flows.
- ✅ **AD-06 Commission settings UI**: Wire to backend settings API with audit trail.
- ✅ **AD-07 Health monitor UI**: Flagged orders view with alerts for stalled orders.

## Mobile (Android Driver) Tickets
- ✅ **MO-01 Environment config**: Replace hardcoded base URL with build-time envs.
- **MO-02 Auth token refresh**: Add refresh interceptor and retry policy for flaky networks.
- **MO-03 Driver workflow**: Add accept/pickup/deliver actions once endpoints exist.
- **MO-04 Live location updates**: Background location streaming with throttling.
- **MO-05 Notifications handling**: FCM payload handling for new assignments and cancellations.
