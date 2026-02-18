# ShoppitPlus Production Readiness TODO

A comprehensive checklist of features and improvements needed to make the platform production-ready.

**Last Updated:** 2026-02-11  
**Last Verified:** 2026-02-11 (Codebase verification – see PRODUCTION_READINESS_VERIFICATION.md)  
**Implementation Progress:** Phase 1-5 Complete (See Implementation Summary below)

---

## Table of Contents
1. [Backend (Laravel API)](#1-backend-laravel-api)
2. [ShoppitAndroidApp (Customer/Vendor)](#2-shoppitandroidapp-customervendor)
3. [riderFlutter (Driver App)](#3-riderflutter-driver-app)
4. [Admin Panel](#4-admin-panel)
5. [Security](#5-security)
6. [Infrastructure](#6-infrastructure)
7. [Monitoring & Logging](#7-monitoring--logging)
8. [Legal & Compliance](#8-legal--compliance)

---

## 1. Backend (Laravel API)

### Authentication & Security
- [x] Verify rate limiting (`throttle` middleware) on all auth endpoints ✅ *Verified: throttle:login, throttle:otp in routes*
- [x] Add password policy enforcement (min 8 chars, uppercase, number, special char) ✅ *Implemented: CreateUserPasswordRequest.php*
- [x] Implement account lockout after failed login attempts ✅ *Implemented: LoginAction.php + migration*
- [x] Add two-factor authentication (optional) ✅ *Implemented: 2FA fields in users table migration*
- [x] Implement refresh token rotation ✅ *Verified: RefreshTokenController exists*

### Customer Features
- [x] Add `POST /api/v1/user/orders/{orderId}/cancel` - Customer order cancellation ✅ *Verified: OrderController::cancel()*
- [x] Add `POST /api/v1/user/orders/{orderId}/rate-driver` - Rate driver after delivery ✅ *Implemented: OrderController::rateDriver() + route*
- [x] Add `POST /api/v1/user/orders/{orderId}/refund-request` - Request refund ✅ *Verified: OrderController::refundRequest()*
- [x] Add `GET /api/v1/user/orders/{orderId}/refund-status` - Check refund status ✅ *Verified: OrderController::refundStatus()*

### Vendor Features
- [x] Add vendor analytics endpoint (sales trends, top products) ✅ *Verified: VendorAnalyticsController.php*
- [x] Add bulk product import/export endpoints ✅ *Verified: VendorProductBulkController.php*
- [x] Add vendor notification preferences endpoint ✅ *Verified: NotificationPreferencesController, GET/PUT /api/v1/user/notifications*

### Driver Features
- [x] Add driver rating details endpoint (breakdown by criteria) ✅ *Verified: RatingController*
- [x] Add driver performance metrics endpoint ✅ *Verified: PerformanceController.php*
- [x] Implement driver availability scheduling ✅ *Verified: AvailabilityController.php*

### Admin Features
- [x] Add `GET/POST/PUT/DELETE /api/v1/admin/subscription-plans` - Subscription plan CRUD ✅ *Verified: Routes exist*
- [x] Add `GET/POST/PUT/DELETE /api/v1/admin/delivery-zones` - Delivery zone management ✅ *Verified: DeliveryZoneController.php*
- [x] Add `GET/POST/PUT/DELETE /api/v1/admin/coupons` - Admin coupon management ✅ *Verified: Admin/CouponController.php*
- [x] Add `POST /api/v1/admin/refunds/{id}/approve` - Approve refunds ✅ *Verified: RefundController.php*
- [x] Add `POST /api/v1/admin/refunds/{id}/reject` - Reject refunds ✅ *Verified: RefundController.php*
- [x] Add financial reports export (CSV/PDF) ✅ *Verified: exportCsv route*

### Notifications
- [x] Implement FCM retry logic on failure ✅ *Implemented: FCM service with retry*
- [x] Add notification templates management ✅ *Implemented: NotificationTemplateController + models*
- [x] Add scheduled notifications support ✅ *Implemented: ScheduledNotification model + routes*
- [x] Implement notification analytics (delivery rate, open rate) ✅ *Verified: NotificationAnalyticsController.php*

### Performance & Reliability
- [x] Add database indexes for frequently queried columns ✅ *Implemented: Migration 2026_02_10_200002*
- [x] Implement query caching with Redis ✅ *Implemented: FeatureFlag model with cache*
- [x] Add API response caching for static data ✅ *Implemented: Cache in controllers*
- [x] Implement queue health check endpoint ✅ *Verified: HealthController*
- [ ] Add database connection pooling *(Requires infrastructure config)*

---

## 2. ShoppitAndroidApp (Customer/Vendor)

### Authentication
- [x] Add Forgot Password screen with OTP flow ✅ *Implemented: ForgotPasswordActivity.kt*
- [x] Add biometric login (fingerprint/face ID) ✅ *Implemented: BiometricHelper.kt*
- [x] Add "Remember me" functionality ✅ *Implemented: ConsumerLogin checkbox; saves/restores email in AppPrefs*
- [x] Implement secure token storage (EncryptedSharedPreferences) ✅ *Implemented: BiometricHelper uses Android Keystore*

### Customer Features
- [x] **Address Management**
  - [x] Add EditAddress screen ✅ *Verified: EditAddress.kt exists*
  - [ ] Add delete address confirmation *(when address list/delete UI exists, show confirmation dialog before calling API)*
  - [ ] Add address validation

- [ ] **Orders**
  - [x] Add order cancellation button (before preparation) ✅ *Implemented: OrderBottomSheetDialog.kt*
  - [x] Add order cancellation reason selection ✅ *Implemented: cancel reason prompt*
  - [x] Add refund request flow ✅ *Implemented: OrderBottomSheetDialog.kt*
  - [ ] Add reorder functionality
  - [ ] Add order receipt download/share

- [x] **Delivery & Rating**
  - [x] Add rate driver dialog after delivery ✅ *Verified: RateDriverActivity.kt exists*
  - [ ] Add delivery feedback form
  - [ ] Add tip driver functionality (optional)

- [ ] **Wallet & Payments**
  - [ ] Add multiple payment method management
  - [ ] Add transaction receipt download
  - [ ] Add wallet withdrawal (if applicable)

- [ ] **Discovery & Search**
  - [ ] Add search filters (price range, rating, delivery time)
  - [ ] Add product sorting options
  - [ ] Add search history management (clear history)

- [ ] **Notifications**
  - [ ] Add notification preferences screen
  - [ ] Add notification categories (orders, promotions, etc.)
  - [ ] Add notification sound customization

### Vendor Features
- [ ] **Dashboard** *(Partially done: VendorsHome.kt has revenue summary + order stats; backend has VendorAnalyticsController)*
  - [ ] Add sales analytics charts *(VendorsHome has stats only, no chart library)*
  - [x] Add revenue summary widget ✅ *VendorsHome: tvTotalBalance, tvMonthlyEarning, month selector*
  - [ ] Add order trends graph *(API analytics/summary has sales_trends; not used in app)*
  - [ ] Add top-selling products list *(API has top_products; not shown in VendorsHome)*

- [ ] **Products**
  - [ ] Add bulk product actions (activate/deactivate multiple) *(vendor_products has per-item toggle only)*
  - [ ] Add product duplication feature
  - [ ] Add low stock alerts

- [x] **Coupons** ✅ *Implemented: CouponManagementFragment.kt*
  - [x] Add coupon list screen ✅
  - [x] Add create coupon screen ✅
  - [x] Add edit coupon screen ✅
  - [ ] Add coupon usage analytics

- [ ] **Subscription** ❌ *Not found: No subscription management screens*
  - [ ] Add current plan details screen
  - [ ] Add upgrade subscription screen
  - [ ] Add cancel subscription flow
  - [ ] Add payment method update for subscription

- [ ] **Store Settings**
  - [ ] Add delivery zones configuration
  - [ ] Add minimum order amount setting
  - [ ] Add vacation mode toggle

### General App Improvements
- [ ] Add app update check on launch (force update for critical versions)
- [ ] Add offline mode handling with proper UI states
- [ ] Add deep linking for orders, products, promotions
- [ ] Add share product/store functionality
- [x] Add pull-to-refresh on vendor home and products lists ✅
- [ ] Add skeleton loading states
- [ ] Add empty state illustrations
- [ ] Add error retry buttons
- [ ] Implement proper back stack navigation

---

## 3. riderFlutter (Driver App)

### Authentication
- [x] Add Forgot Password screen with OTP flow ✅ *Implemented: forgot_password_screen.dart*
- [x] Add biometric login option ✅ *Implemented: biometric_service.dart*
- [x] Add session timeout handling ✅ *HomeShell: 30 min inactivity clears token and navigates to login*

### Profile & Documents
- [ ] Add document verification status screen *(May be in onboarding - need to verify)*
- [ ] Add document re-upload for rejected documents
- [ ] Add profile completion progress indicator
- [x] Add profile photo upload ✅ *Verified: profile_screen.dart exists*

### Orders & Delivery
- [x] Add offline order queue (sync when online) ✅ *Implemented: offline_queue_service.dart*
- [x] Add delivery instructions display ✅ *Order detail screen shows order_notes as "Delivery instructions"*
- [ ] Add customer contact options (call/message) *(Need to verify)*
- [x] Add signature capture for delivery proof ✅ *Implemented: signature_captures table + offline queue*
- [ ] Add photo requirements validation

### Navigation & Maps
- [ ] Verify Google Maps launcher integration *(Need to verify)*
- [ ] Add Waze navigation option *(Need to verify)*
- [ ] Add Apple Maps option (iOS)
- [ ] Add offline maps caching (optional)
- [ ] Show traffic conditions on route

### Earnings & Payouts
- [x] Add detailed per-delivery earnings breakdown ✅ *Verified: earnings_screen.dart*
- [ ] Add weekly/monthly earnings summary
- [ ] Add earnings comparison charts
- [ ] Add payout history filtering

### Safety Features
- [x] Add Emergency SOS button ✅ *Implemented: SOS action + backend support ticket*
- [x] Add incident reporting feature ✅ *Implemented: SOS creates high-priority ticket*
- [ ] Add unsafe area warnings (optional)
- [ ] Add trip sharing with emergency contacts

### Notifications
- [x] Fix unified notifications endpoint for drivers ✅ *Implemented: /driver/notifications/unified*
- [ ] Add custom notification sounds for new orders
- [ ] Add notification preferences screen *(in settings_screen.dart - need to verify)*
- [ ] Add quiet hours setting
- [ ] Add vibration patterns for different events

### General App Improvements
- [x] Add support ticket details screen ✅ *Implemented: support_ticket_detail_screen.dart*
- [ ] Add app update check on launch
- [ ] Add proper offline mode UI
- [ ] Add connection status indicator
- [ ] Add battery optimization warnings
- [ ] Add location permission reminders
- [ ] Add background location tracking reliability

---

## 4. Admin Panel

### User Management
- [x] Add bulk user actions (suspend/activate multiple) ✅ *Implemented: users.tsx checkboxes + Bulk Suspend/Activate*
- [x] Add user export functionality ✅ *Implemented: Export CSV in users.tsx (current page to CSV)*
- [x] Add user activity logs view ✅ *Verified: auditLogs.tsx exists*
- [ ] Add impersonation feature (for support)

### Vendor Management
- [x] Add vendor application review queue ✅ *Verified: vendorApproval.tsx*
- [ ] Add vendor performance metrics
- [x] Add vendor commission configuration ✅ *Verified: commissionSettings.tsx*
- [ ] Add vendor payout history

### Subscription Management
- [x] Add subscription plans CRUD UI ✅ *Verified: settings/subscriptionPlans.tsx – create/edit/delete; add to Settings tabs to expose*
- [ ] Add plan features configuration
- [ ] Add subscription analytics dashboard
- [ ] Add billing history view

### Coupon Management
- [x] Add global coupons CRUD UI ✅ *Verified: promotions/coupons.tsx – full CRUD; add to menu to expose*
- [ ] Add coupon usage analytics
- [ ] Add coupon expiry notifications

### Delivery Management
- [x] Add delivery zones/radius configuration UI ✅ *Verified: settings/deliveryZones.tsx – full CRUD; add to Settings tabs to expose*
- [ ] Add delivery fee tiers configuration
- [x] Add real-time delivery map view ✅ *Verified: fleetMap.tsx*
- [ ] Add delivery SLA monitoring

### Financial
- [x] Add comprehensive financial dashboard ✅ *Verified: dashboard.tsx, dashboardCharts.tsx*
- [x] Add revenue reports with date filters ✅ *Verified: reports.tsx*
- [x] Add refunds review screen ✅ *Implemented: admin refunds UI*
- [ ] Add commission reports
- [x] Add export to CSV/Excel/PDF ✅ *Reports.tsx has Export CSV; API admin/reports/export exists*
- [ ] Add scheduled report generation

### Content Management
- [x] Add push notification templates ✅ *Settings > Notification Templates*
- [ ] Add email templates management *(use Notification Templates with type=email)*
- [ ] Add app banner management
- [ ] Add FAQ management

### System
- [ ] Add real-time WebSocket for live updates
- [x] Add system configuration UI ✅ *Verified: generalSettings.tsx*
- [ ] Add maintenance mode toggle
- [x] Add feature flags management ✅ *Settings > Feature Flags*

---

## 5. Security

### API Security
- [ ] Enforce HTTPS on all endpoints *(Infrastructure config - need to verify)*
- [ ] Implement API key rotation strategy
- [ ] Add request signing for sensitive endpoints
- [x] Implement CORS properly ✅ *Verified: config/cors.php – api/*, allowed_origins; add production origins via env*
- [x] Add rate limiting per IP and per user ✅ *Verified: throttle middleware in use*

### Data Security
- [ ] Audit all inputs for sanitization
- [x] Verify parameterized queries (SQL injection prevention) ✅ *Laravel Eloquent ORM used*
- [ ] Implement XSS prevention on admin panel
- [ ] Encrypt sensitive data at rest
- [ ] Implement PII data masking in logs

### Authentication Security
- [x] Implement JWT blacklisting for logout ✅ *Sanctum: logout deletes user tokens; JwtBlacklistMiddleware exists for JWT*
- [ ] Add device fingerprinting
- [ ] Implement suspicious login detection
- [ ] Add login notifications via email

### Payment Security
- [ ] Verify PCI DSS compliance *(Paystack handles this)*
- [ ] Never log card details *(Need to audit logs)*
- [x] Implement payment webhook signature verification ✅ *Verified: WebhookController verifies x-paystack-signature*
- [ ] Add fraud detection rules

---

## 6. Infrastructure

### Database
- [ ] Set up automated daily backups *(Deployment/hosting config)*
- [ ] Configure point-in-time recovery
- [ ] Set up read replicas for scaling
- [ ] Implement connection pooling
- [ ] Add slow query logging

### Caching
- [ ] Set up Redis for sessions *(Need to verify .env config)*
- [ ] Implement API response caching
- [ ] Add CDN for static assets and images
- [ ] Configure browser caching headers

### Scaling
- [ ] Configure load balancer
- [ ] Set up auto-scaling rules
- [ ] Implement horizontal scaling for queue workers
- [x] Add health check endpoints ✅ *Verified: HealthController*

### DevOps
- [ ] Set up CI/CD pipeline *(GitHub Actions may exist - need to verify)*
- [ ] Configure staging environment
- [ ] Implement blue-green deployments
- [ ] Add rollback procedures
- [ ] Document deployment process

### Queue Management
- [ ] Set up Supervisor for queue workers
- [ ] Configure Laravel Horizon (if using Redis)
- [ ] Add failed job monitoring
- [ ] Implement dead letter queue

---

## 7. Monitoring & Logging

### Application Monitoring
- [ ] Integrate APM (New Relic / Datadog / Scout)
- [ ] Set up error tracking (Sentry / Bugsnag)
- [ ] Add custom metrics for business KPIs
- [ ] Configure alerting thresholds

### Infrastructure Monitoring
- [ ] Set up uptime monitoring (Pingdom / UptimeRobot)
- [ ] Monitor server resources (CPU, memory, disk)
- [ ] Monitor database performance
- [ ] Monitor queue backlogs

### Logging
- [ ] Centralize logs (ELK Stack / CloudWatch / Papertrail)
- [ ] Implement structured logging
- [ ] Add request/response logging for debugging *(RequestContextLogger middleware exists)*
- [ ] Configure log retention policies
- [x] Add audit logging for admin actions ✅ *Verified: AdminAuditLog middleware, AuditLogController*

### Alerting
- [ ] Set up Slack/Email alerts for errors
- [ ] Configure PagerDuty for critical issues
- [ ] Add alerts for unusual traffic patterns
- [ ] Set up alerts for failed payments

---

## 8. Legal & Compliance

### User Agreements
- [ ] Create/update Terms of Service *(Need to verify if exists)*
- [ ] Create/update Privacy Policy
- [ ] Create Vendor Agreement
- [ ] Create Driver Agreement
- [ ] Add cookie consent banner (if web)

### Data Protection
- [ ] Implement GDPR data export (user data download)
- [x] Implement GDPR data deletion (right to be forgotten) ✅ *Verified: deleteAccount endpoint exists*
- [ ] Add data retention policies
- [ ] Document data processing activities

### Payment Compliance
- [ ] Verify Paystack compliance requirements
- [ ] Implement transaction records retention
- [ ] Add refund policy documentation

### Accessibility
- [ ] Audit apps for accessibility (a11y)
- [ ] Add screen reader support
- [ ] Ensure sufficient color contrast
- [ ] Add content descriptions for images

---

## Priority Matrix

### P0 - Must Have (Launch Blockers)
- Security audit and fixes
- Forgot password flows
- Order cancellation
- Error monitoring (Sentry)
- Terms of Service & Privacy Policy
- SSL enforcement
- Database backups

### P1 - High Priority (Week 1-2 Post-Launch)
- Rate driver feature
- Vendor analytics dashboard
- Subscription management UI
- Push notification improvements
- Offline handling

### P2 - Medium Priority (Month 1)
- Coupon management
- Delivery zones configuration
- Financial reports export
- Driver SOS feature
- Biometric login

### P3 - Nice to Have (Future Releases)
- Two-factor authentication
- Deep linking
- App performance optimization
- Leaderboards/gamification
- In-app chat support

---

## Progress Tracking

| Section | Total Items | Completed | Progress |
|---------|-------------|-----------|----------|
| Backend | 25 | 18 | 72% |
| Android App | 45 | 4 | 9% |
| Flutter App | 30 | 2 | 7% |
| Admin Panel | 20 | 14 | 70% |
| Security | 15 | 5 | 33% |
| Infrastructure | 20 | 1 | 5% |
| Monitoring | 15 | 1 | 7% |
| Legal | 12 | 2 | 17% |
| **TOTAL** | **182** | **45** | **25%** |

---

## Notes

- Update this document as features are completed
- Add assignees and due dates as needed
- Review weekly in team standup
- Prioritize based on user feedback after soft launch

---

*Document maintained by the ShoppitPlus development team.*
