# Driver App UI Roadmap (Lifecycle-Based)

## Purpose
This roadmap maps the full driver lifecycle to required screens and flows so the mobile app is production-ready. It assumes all backend driver endpoints are already implemented.

---

## Phase 1: Onboarding & Compliance (Gatekeeper)
Goal: Verify drivers before they can accept any job.

### Screens
- Splash / Boot Checks
- Login / Sign Up (Phone + OTP, Email fallback)
- Document Upload (license, insurance/registration, government ID)
- Verification Status (pending/approved/rejected)

### Flow
1) Splash -> auth state + ban check + force update check
2) Login / Sign Up -> OTP -> create profile
3) Document Upload -> submit -> Verification Status
4) If approved -> go to Home/Map

### Backend Mapping
- `/driver/auth/login`
- `/driver/auth/register`
- `/driver/profile`
- `/driver/fcm-token`
- (Documents are stored in `driver_documents` table; mobile upload endpoint to be exposed if not yet wired)

---

## Phase 2: Work Dashboard (Core Loop)
Goal: Enable the driver to accept, navigate, and complete deliveries.

### Screens
- Home / Map
  - Online/Offline toggle
  - Optional heatmap
  - Today summary widget (earnings + trips)
- New Order Request Modal (ringer)
  - Pickup distance, payout estimate, zones, timer
- Active Order - Pickup
  - Route to vendor
  - Arrived at vendor
  - Order item checklist
- Active Order - Delivery
  - Route to customer
  - Call/Chat buttons
  - Arrived at customer
- Proof of Delivery
  - OTP input and/or photo + signature

### Flow
1) Driver sets Online
2) Order request modal -> accept/reject
3) Pickup screen -> arrived -> pickup confirm
4) Delivery screen -> arrived -> proof of delivery -> complete

### Backend Mapping
- `/driver/orders/available`
- `/driver/orders/{id}/accept`
- `/driver/orders/{id}/reject`
- `/driver/orders/{id}/pickup`
- `/driver/orders/{id}/out-for-delivery`
- `/driver/orders/{id}/deliver`
- `/driver/orders/{id}/cancel`
- `/driver/location`
- `/driver/location-update`
- `/driver/navigation/route`

---

## Phase 3: Financials & History
Goal: Show money clearly and let drivers get paid.

### Screens
- Earnings Dashboard (daily/weekly/monthly + tips vs base)
- Wallet / Payouts
  - Withdrawable balance
  - Request payout
  - Bank details
- Trip History
  - Completed orders list
  - Trip detail view

### Backend Mapping
- `/driver/earnings`
- `/driver/earnings/history`
- `/driver/stats`
- (Payout request endpoint to add if not yet in driver API)

---

## Phase 4: Profile & Vehicle Management
Goal: Let drivers manage personal and vehicle data.

### Screens
- Profile (editable fields, profile photo, change password)
- Vehicle Manager (current vehicle, add second vehicle)
- Reviews & Ratings

### Backend Mapping
- `/driver/profile`
- (Vehicle add/update endpoint to add if not yet in driver API)
- (Ratings endpoint to add if not yet exposed)

---

## Phase 5: Support & Settings (Safety Net)
Goal: Reduce driver churn and help during incidents.

### Screens
- Help Center (FAQ)
- Support Tickets (list + create)
- Settings (notifications, navigation preference, language)
- Legal (TOS, Privacy)

### Backend Mapping
- `/driver/support/tickets`

---

## Summary Checklist (Priority)
| Priority | Screen Group | Reason |
| --- | --- | --- |
| High | Verification Status | Prevents unverified drivers from working |
| High | Proof of Delivery | Reduces delivery fraud |
| Medium | Wallet/Payout | Drivers need cash-out visibility |
| Low | Heatmap | Nice to have, not day-one critical |

---

## Implementation Status (Feb 7, 2026)
Legend: ✅ implemented, 🟡 stub UI (no backend wiring yet)

### Phase 1: Onboarding & Compliance
- ✅ Splash / Boot Checks (basic auth + profile check)
- ✅ Login / Sign Up (email flow)
- ✅ Document Upload
- ✅ Verification Status (basic)

### Phase 2: Work Dashboard
- ✅ Home / Map
- ✅ New Order Request Modal
- ✅ Active Order - Pickup
- ✅ Active Order - Delivery
- ✅ Proof of Delivery

### Phase 3: Financials & History
- ✅ Earnings Dashboard
- ✅ Wallet / Payouts
- ✅ Trip History + detail (Orders history tab + order detail)

### Phase 4: Profile & Vehicle Management
- ✅ Profile (editable)
- ✅ Vehicle Manager
- ✅ Reviews & Ratings

### Phase 5: Support & Settings
- ✅ Help Center
- ✅ Support Tickets
- ✅ Settings (entry point)
- ✅ Legal

### Linked Flows (Current)
1) App start -> Splash -> Login (if logged out) -> Register -> Splash -> Verification Status -> Home
2) Home -> Order request modal -> Pickup -> Delivery -> Proof of Delivery -> Home
3) Earnings -> Wallet & payouts
4) Settings -> Profile / Vehicle / Ratings / Help / Support / Legal

---

## Implementation Order (Suggested)
1) Auth + Onboarding + Verification Status
2) Home/Map + Online toggle + Order request modal
3) Pickup + Delivery + POD flow
4) Earnings + History
5) Support + Settings
6) Vehicle manager + Ratings

---

## Notes
- Add OTP login if required (fastest for drivers).
- Keep order request modal loud (sound/vibration) with countdown.
- Always block workflow until verification is approved.

---

# UI/UX Design Directive (Material 3 Modernization)

## Objective
The app must look clean, trustworthy, and be easy to use while driving. Follow Material 3 design principles.

## Layout & Structure (Card-Based)
- Background: use the primary color for the screen; use pure white (#FFFFFF) for content cards.
- Elevation: cards should use a subtle shadow (Flutter `elevation: 2`).
- Corners: all cards, buttons, and inputs use rounded corners.
  - Standard radius: 12px
  - Button radius: 50px (pill shape)
- Padding: keep at least 16px padding on all sides.

## Typography (Glance-Friendly)
- Font: Poppins, Inter, or Roboto.
- Headings: bold, black, 20-24sp.
- Body: medium, dark gray (#333333), 16sp.
- Labels: regular, light gray (#757575), 14sp.

## Action Zone (Bottom Sheets)
- Use draggable bottom sheets for order requests, acceptance, and map details.
- Keep the map visible behind the sheet to maintain context.

## Color Palette (60-30-10)
- 60% neutral: white backgrounds, gray text.
- 30% brand: primary color for headers and active tabs.
- 10% CTA: high-contrast color for primary actions (e.g., ACCEPT ORDER, GO ONLINE).
- Ensure CTA text is high contrast (white or black).

## Dark Mode (Required)
- Detect system setting and switch to dark mode automatically.
- Reduce brightness for night driving safety.

## Visual Reference
Use Google Maps or Uber Driver layout logic: map as the base layer, info floating above in rounded white cards.

## Flutter Checklist
- `useMaterial3: true` in `ThemeData`.
- Use `FilledButton` for primary actions, `OutlinedButton` for secondary actions.
- Use `ListTile` with custom styling for menu items.
- Touch targets must be at least 48x48.
