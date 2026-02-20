# Delivery System Wiring Audit

This document summarizes how delivery is wired across the admin dashboard, ShoppitAndroidApp (users/vendors), and riderFlutter (driver app).

## Admin Dashboard (`SHOPPITAPPS/admin`)

### Delivery Section
- **Location:** `Delivery` menu → `delivery.tsx` with tabs:
  - **Live Fleet Map** – `GET /api/v1/admin/drivers/locations` for live driver positions
  - **Analytics Dashboard** – `GET /api/v1/admin/drivers`, `GET /api/v1/admin/drivers/{id}/stats`, `GET /api/v1/admin/analytics/performance`
  - **Driver Management** – `GET /api/v1/admin/drivers`, `POST /api/v1/admin/drivers/{id}/verify`, `POST /api/v1/admin/drivers/{id}/block`, `POST /api/v1/admin/drivers/{id}/unblock`
  - **Driver Payouts** – `GET /api/v1/admin/payouts?include_pending=1`, `POST /api/v1/admin/payouts/{driverId}/approve`, `GET /api/v1/admin/payouts/export`, `GET /api/v1/admin/payouts/reconcile`

### Orders Section
- **Order Management** – `GET /api/v1/admin/order-management`, `POST /api/v1/admin/order-management/{id}/update-status`, `POST /api/v1/admin/order-management/{id}/reassign` (driver reassignment)

### API Base
- Dev: `http://localhost/shopittplus-api/public` (or `VITE_API_BASE_URL`)
- Prod: `https://laravelapi-production-1ea4.up.railway.app`

---

## ShoppitAndroidApp (Users & Vendors)

### User (Consumer) – Order & Delivery
| Feature | API Path | Notes |
|---------|----------|-------|
| Orders list | `GET user/orders` | |
| Order details | `GET user/orders/{id}` | |
| Order tracking | `GET user/orders/{id}/track` | Returns `status`, `driver`, `driver_location`, `delivery_location` |
| Order ETA | `GET user/orders/{id}/eta` | |
| Rate driver | `POST user/orders/{orderId}/rate-driver` | |

### Vendor – Order & Delivery
| Feature | API Path | Notes |
|---------|----------|-------|
| Orders list | `GET user/vendor/orders` | |
| Order details | `GET user/vendor/orders/{id}` | |
| Order tracking | `GET user/vendor/orders/{id}/track` | Returns driver info, locations |
| Order ETA | `GET user/vendor/orders/{id}/eta` | |
| Update status | `PUT user/vendor/orders/{id}/status` | |

### Cart & Checkout
- Cart: `delivery_fee` from `VendorCartResponse` / `CartResponse`
- Process cart: `ProcessCartRequest` includes `receiver_delivery_address`

### API Base
- Debug: `http://10.0.2.2/shopittplus-api/public/api/v1/`
- Release: `https://laravelapi-production-1ea4.up.railway.app/api/v1/`

---

## riderFlutter (Driver App)

### Auth & Profile
- Register: `POST /driver/auth/register`
- Login: `POST /driver/auth/login`, `POST /driver/auth/login-otp`
- Profile: `GET/PUT /driver/profile`, `POST /driver/profile/avatar`, `POST /driver/profile/password`
- Status: `POST /driver/status`
- FCM: `POST /driver/fcm-token`

### Orders & Delivery
| Action | API Path |
|--------|----------|
| Available orders | `GET /driver/orders/available?latitude=&longitude=` |
| Active orders | `GET /driver/orders/active` |
| Order history | `GET /driver/orders/history` |
| Accept | `POST /driver/orders/{id}/accept` |
| Reject | `POST /driver/orders/{id}/reject` |
| Pickup | `POST /driver/orders/{id}/pickup` |
| Out for delivery | `POST /driver/orders/{id}/out-for-delivery` |
| Deliver | `POST /driver/orders/{id}/deliver` |
| Upload POD | `POST /driver/orders/{id}/pod` |
| Cancel | `POST /driver/orders/{id}/cancel` |

### Location
- Initial: `POST /driver/location`
- Updates: `POST /driver/location-update`

### API Base
- Dev: `http://10.0.2.2/shopittplus-api/public/api/v1` or `http://10.0.2.2:8000/api/v1`
- Prod: `https://laravelapi-production-1ea4.up.railway.app/api/v1`

---

## Delivery Zones

### Admin
- **Location:** Settings → Delivery Zones (`/api/v1/admin/delivery-zones`)
- **CRUD:** GET (list), POST (create), PUT `/{id}` (update), DELETE `/{id}`
- **Schema:** name, description, areas (array), **center_latitude**, **center_longitude**, **radius_km**, base_fee, per_km_fee, min_order_amount, estimated_time_min, estimated_time_max, is_active
- **Geo mapping:** Admin sets center (lat/lng) and radius (km). "Use my current location" fills center from browser geolocation.
- **Persistence:** Stored in `delivery_zones` table.

### Public zone check (for registration)
- **Endpoint:** `GET /api/v1/delivery-zones/check?latitude=6.5244&longitude=3.3792`
- **Response:** `{ success, in_zone: boolean, zone: { id, name, base_fee, ... } | null }`
- **Use:** User, vendor, rider apps call this before/during registration to verify location is in a delivery zone.

### Zone validation at order placement (implemented)

Registration is **not** blocked by zone. Users, vendors, and riders can register freely.

**At checkout (ShoppitAndroidApp):**
- Advisory shown: "Select the delivery zone for your order. Ensure you are at your delivery address—your location will be verified to match our service zones."
- Before placing order: app gets device location, calls `GET /delivery-zones/check`. If not in zone, shows error and blocks payment.
- `ProcessCartRequest` includes `delivery_latitude`, `delivery_longitude`. Backend validates point is in an active zone before processing.

**riderFlutter** – Riders deliver; no zone check at registration.

**riderFlutter "zone"** – display-only label from vendor city/state (`"$city, $state"`), not delivery zones

---

## Backend Routes Summary

### User
- `GET user/orders/{orderId}/track` → OrderTrackingController (user_id)
- `GET user/orders/{orderId}/eta`

### Vendor
- `GET user/vendor/orders/{orderId}/track` → Vendor OrderTrackingController (vendor_id)
- `GET user/vendor/orders/{orderId}/eta`

### Driver
- All under `driver/` prefix with `auth:driver` middleware
- Orders: available, active, history, accept, reject, pickup, out-for-delivery, deliver, pod, cancel

### Admin
- All under `api/v1/admin/` with `auth:admin` middleware
- Drivers: index, locations, verify, block, unblock
- Payouts: index, export, reconcile, approve
- Order management: update status, reassign

---

## Fixes Applied (Feb 2025)

1. **Admin Delivery** – Added **Driver Payouts** tab to the main Delivery section so admins can approve payouts and export.
2. **Vendor OrderTrackingController** – Fixed authorization: use `Auth::user()->vendor->id` instead of `Auth::id()` when filtering orders by `vendor_id`, since `orders.vendor_id` references `vendors.id`, not `users.id`.
