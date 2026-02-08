# ShopittPlus Driver, Admin, User/Vendor App Roadmap

## Driver App (riderFlutter)

### Endpoint Status (Laravel)
- `/api/v1/driver/support/tickets` (GET/POST): Implemented
- `/api/v1/driver/navigation/route` (POST): Implemented
- `/api/v1/driver/orders/{id}/cancel` (POST): Implemented
- `/api/v1/driver/stats` (GET): Implemented

### Actionable Tickets (per file, per endpoint)
- **Route Optimization UI (dedicated screen)** ✅ DONE
  - Endpoint: `/api/v1/driver/navigation/route`
  - Files: `riderFlutter/lib/features/home/presentation/home_map_screen.dart`
  - Tasks: add a dedicated route optimization screen or modal, link from Home/Orders; render route details (ETA, stops).
- **Order Cancellation Reason + Penalty** ✅ DONE
  - Endpoint: `/api/v1/driver/orders/{id}/cancel`
  - Files: `riderFlutter/lib/features/orders/presentation/active_pickup_screen.dart`, `riderFlutter/lib/features/orders/presentation/active_delivery_screen.dart`, `riderFlutter/lib/features/orders/data/order_service.dart`
  - Tasks: collect cancellation reason, show penalty confirmation, pass payload to cancel API.
- **Driver Stats Dashboard** ✅ DONE
  - Endpoint: `/api/v1/driver/stats`
  - Files: `riderFlutter/lib/features/earnings/presentation/earnings_screen.dart`, `riderFlutter/lib/features/home/presentation/home_map_screen.dart`
  - Tasks: create a dedicated stats dashboard screen; link from Earnings/Home; include delivery count, ratings, earnings, cancellation rate.
- **Centralized Error/Alert Handling** ✅ DONE
  - Endpoints: N/A (UI layer)
  - Files: `riderFlutter/lib/core/network/api_response.dart`, shared widgets folder
  - Tasks: add alert banner/toast system for blocked account, payout failures, penalty notices; standardize error presentation.

---

## Admin Dashboard (admin)

### Endpoint Status (Laravel)
- `/api/v1/admin/drivers/{id}/stats` (GET): Implemented
- `/api/v1/admin/analytics/heatmap` (GET): Implemented
- `/api/v1/admin/analytics/performance` (GET): Implemented
- `/api/v1/admin/audits` (GET): Implemented (note: roadmap previously used `/audit-logs`)
- `/api/v1/admin/alerts/status` (GET): Implemented
- `/api/v1/admin/alerts/summary` (GET): Implemented
- `/api/v1/admin/alerts/history` (GET): Implemented
- **Support Ticket Management:** Admin endpoint implemented

### Actionable Tickets (per file, per endpoint)
- **Order Density Heatmap Screen** ✅ DONE
  - Endpoint: `/api/v1/admin/analytics/heatmap`
  - Files: `admin/src/components/` (new component), `admin/src/components/main.tsx` (navigation)
  - Tasks: add heatmap view with map component; wire API and filters (date range, status).
- **Audit Logs Viewer** ✅ DONE
  - Endpoint: `/api/v1/admin/audits`
  - Files: `admin/src/components/` (new component), `admin/src/components/main.tsx`
  - Tasks: table view with filters (admin, action, date), pagination.
- **Health Monitor & Alerts Screen** ✅ DONE
  - Endpoints: `/api/v1/admin/alerts/status`, `/api/v1/admin/alerts/summary`, `/api/v1/admin/alerts/history`, `/api/v1/admin/health`
  - Files: `admin/src/components/` (new component), `admin/src/components/dashboard.tsx` (link out), `admin/src/components/main.tsx`
  - Tasks: dedicated health/alerts dashboard with history view and alert drilldown.
- **Support Ticket Management (Admin)** ✅ DONE
  - Endpoint: create admin API (list, view, update status, respond)
  - Files: `app/Http/Controllers/Api/V1/Admin/` (new controller), `routes/admin/v1/api.php` (routes), `admin/src/components/` (new UI), `admin/src/components/main.tsx`
  - Tasks: implement admin ticket management flow (list, details, status update, response).

---

## User/Vendor App (ShoppitAndroidApp)

### Endpoint Status (Laravel)
- Order tracking: `/user/orders/{orderId}/track`, `/user/orders/{orderId}/eta` (GET) implemented
- Reviews: `/user/reviews` (GET/POST), `/driver/ratings` (GET) implemented
- Vendor store hours: `/user/vendor/store/hours` (PUT) implemented
- Vendor payouts: `/user/vendor/payouts` (GET), `/user/vendor/payouts/withdraw` (POST), `/user/vendor/earnings/summary` (GET) implemented

### Actionable Tickets (per file, per endpoint)
- **Order Tracking / Live Tracking (User)** ✅ DONE
  - Endpoints: `/user/orders/{orderId}/track`, `/user/orders/{orderId}/eta`
  - Files: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/models/Api.kt`, `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/ui/OrderBottomSheetDialog.kt`, new tracking fragment/activity + map layout
  - Tasks: add API calls, create tracking UI with map + driver marker, wire "Track Order" button.
- **Rate/Review Driver (User)** ✅ DONE
  - Endpoints: `/user/reviews` (GET/POST), `/driver/ratings` (GET)
  - Files: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/models/Api.kt`, `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/ui/OrderBottomSheetDialog.kt`, new rating fragment/activity
  - Tasks: add review API calls, build rating UI, attach to order detail flow.
- **Live Order Tracking (Vendor)** ✅ DONE
  - Endpoints: `/user/vendor/orders/{orderId}/track`, `/user/vendor/orders/{orderId}/eta`
  - Files: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/vendor/OrderDetails.kt`, new tracking UI (reuse map component)
  - Tasks: add vendor tracking view with driver location and ETA.
- **Vendor Store Hours Management** ✅ DONE
  - Endpoint: `/user/vendor/store/hours` (PUT)
  - Files: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/models/Api.kt`, new vendor settings fragment, `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/utils/VendorResponse.kt`
  - Tasks: add hours edit UI, submit updates, display current hours.
- **Vendor Payouts & Transactions** ✅ DONE
  - Endpoints: `/user/vendor/payouts`, `/user/vendor/payouts/withdraw`, `/user/vendor/earnings/summary`
  - Files: `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/Transaction.kt`, `ShoppitAndroidApp/app/src/main/res/layout/fragment_transaction.xml`, `ShoppitAndroidApp/app/src/main/java/com/shoppitplus/shoppit/models/Api.kt`
  - Tasks: implement transaction list UI, payout history, withdraw flow.

---

## Implementation Notes
- For each missing endpoint, create Laravel controller, route, and model as needed.
- For each missing screen, design UI/UX, connect to backend API, and handle error states.
- Ensure real-time updates (WebSocket/FCM) for tracking, notifications, and alerts.
- Add validation, error handling, and business logic for all new features.
- Document API shapes and expected payloads for frontend-backend integration.

---

This roadmap is your reference for tracking and implementing all missing features for a production-ready platform. Update this file as progress is made.
