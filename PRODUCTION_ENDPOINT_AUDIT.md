# Production Endpoint Audit (Android + Admin)

This audit maps recommended screens to actual route paths in routes/v1/api.php and routes/admin/v1/api.php. Status reflects current code; items marked "Stub" are implemented as minimal placeholders.

## Consumer App (Android User)

| Area | Method | Path | Controller | Status | Notes |
| --- | --- | --- | --- | --- | --- |
| Auth | POST | /auth/register | RegisterController | Exists | Shape A2 |
| Auth | POST | /auth/login | LoginController | Exists | Shape A1 |
| Auth | POST | /auth/refresh | RefreshTokenController | Exists | Shape A3 |
| Auth | POST | /auth/check-email | CheckEmailController | Exists | Wrapper only |
| Auth | POST | /auth/verify-register-otp | VerifyRegisterOtp | Exists | Wrapper only |
| Auth | POST | /auth/resend-register-otp | ResendRegisterOtp | Exists | Wrapper only |
| Auth | POST | /auth/send-code | UserOtpController@send | Exists | Wrapper only |
| Auth | POST | /auth/verify-code | UserOtpController@verify | Exists | Wrapper only |
| Auth | POST | /auth/reset-password | ResetPasswordController | Exists | Wrapper only |
| Account | GET | /user/account | UserController@getAuthentictedUser | Exists | Wrapper only |
| Account | POST | /user/account/setup-profile | UserController@setUpProfile | Exists | Wrapper only |
| Account | POST | /user/account/create-password | UserController@createPassword | Exists | Wrapper only |
| Account | PUT | /user/account/update-profile | UserController@updateProfile | Exists | Wrapper only |
| Account | POST | /user/account/update-avatar | UserController@updateAvatar | Exists | Wrapper only |
| Account | POST | /user/account/delete-account | UserController@deleteAccount | Exists | Wrapper only |
| Discovery | GET | /user/discovery/vendors/nearby | DiscoveryController@nearbyVendors | Exists | Wrapper only |
| Discovery | GET | /user/discovery/products/nearby | DiscoveryController@nearbyProducts | Exists | Wrapper only |
| Discovery | GET | /user/discovery/searches/products | DiscoveryController@searchProducts | Exists | Wrapper only |
| Discovery | GET | /user/discovery/searches/vendors | DiscoveryController@searchVendors | Exists | Wrapper only |
| Discovery | GET | /user/discovery/searches/recent | DiscoveryController@recentSearches | Exists | Wrapper only |
| Discovery | GET | /user/discovery/vendors/{vendorId} | DiscoveryController@vendorDetails | Exists | Wrapper only |
| Discovery | GET | /user/discovery/products/{productId} | DiscoveryController@productDetails | Exists | Wrapper only |
| Discovery | POST | /user/discovery/waitlist/join | DiscoveryController@joinWaitlist | Exists | Wrapper only |
| Favorites | GET | /user/favourites/vendors | FavouriteController@favouriteVendors | Exists | Wrapper only |
| Favorites | POST | /user/favourites/vendors/{vendorId} | FavouriteController@addFavouriteVendor | Exists | Wrapper only |
| Favorites | DELETE | /user/favourites/vendors/{vendorId} | FavouriteController@removeFavouriteVendor | Exists | Wrapper only |
| Favorites | GET | /user/favourites/products | FavouriteController@favouriteProducts | Exists | Wrapper only |
| Favorites | POST | /user/favourites/products/{productId} | FavouriteController@addFavouriteProduct | Exists | Wrapper only |
| Favorites | DELETE | /user/favourites/products/{productId} | FavouriteController@removeFavouriteProduct | Exists | Wrapper only |
| Addresses | GET | /user/addresses | AddressController@index | Exists | Wrapper only |
| Addresses | POST | /user/addresses | AddressController@store | Exists | Wrapper only |
| Addresses | PUT | /user/addresses/{id} | AddressController@update | Exists | Wrapper only |
| Addresses | DELETE | /user/addresses/{id} | AddressController@destroy | Exists | Wrapper only |
| Cart | GET | /user/cart | CartController@index | Exists | Wrapper only |
| Cart | GET | /user/cart/vendor/{vendorId} | CartController@vendorCart | Exists | Wrapper only |
| Cart | POST | /user/cart/add | CartController@addItem | Exists | Wrapper only |
| Cart | PUT | /user/cart/item/{itemId} | CartController@updateItem | Exists | Wrapper only |
| Cart | DELETE | /user/cart/item/{itemId} | CartController@removeItem | Exists | Wrapper only |
| Cart | DELETE | /user/cart/vendor/{vendorId} | CartController@clearVendorCart | Exists | Wrapper only |
| Cart | DELETE | /user/cart/clear | CartController@clearCart | Exists | Wrapper only |
| Cart | POST | /user/cart/coupon/{vendorId}/apply | CartController@applyCoupon | Exists | Wrapper only |
| Cart | DELETE | /user/cart/coupon/{vendorId}/remove | CartController@removeCoupon | Exists | Wrapper only |
| Cart | POST | /user/cart/coupon/{vendorId}/validate | CartController@validateCoupon | Exists | Wrapper only |
| Cart | POST | /user/cart/process | CartController@processCart | Exists | Wrapper only |
| Orders | GET | /user/orders | OrderController@index | Exists | Shape O1 |
| Orders | GET | /user/orders/{orderId} | OrderController@show | Exists | Shape O2 |
| Orders | PUT | /user/orders/{orderId}/status | OrderController@updateStatus | Exists | Wrapper only |
| Orders | GET | /user/orders/{orderId}/track | OrderTrackingController@track | Exists | Shape T1 |
| Orders | GET | /user/orders/{orderId}/eta | OrderTrackingController@eta | Exists | Shape T2 |
| Reviews | GET | /user/reviews/{id} | ReviewController@index | Exists | Wrapper only |
| Reviews | POST | /user/reviews | ReviewController@store | Exists | Wrapper only |
| Wallet | GET | /user/wallet/balance | WalletController@balance | Exists | Wrapper only |
| Wallet | GET | /user/wallet/transactions | WalletController@transactions | Exists | Wrapper only |
| Wallet | POST | /user/wallet/deposit | WalletController@deposit | Exists | Wrapper only |
| Payment Methods | GET | /user/payment-methods | PaymentMethodController@index | Exists | Wrapper only |
| Payment Methods | POST | /user/payment-methods | PaymentMethodController@initialize | Exists | Wrapper only |
| Payment Methods | DELETE | /user/payment-methods/{id} | PaymentMethodController@destroy | Exists | Wrapper only |
| Notifications | GET | /user/notifications | UserNotificationController@index | Exists | Wrapper only |
| Notifications | GET | /user/notifications/unread-count | UserNotificationController@unreadCount | Exists | Wrapper only |
| Notifications | GET | /user/notifications/{id} | UserNotificationController@show | Exists | Wrapper only |
| Notifications | POST | /user/notifications/{id}/read | UserNotificationController@markRead | Exists | Wrapper only |
| Notifications | POST | /user/notifications/mark-all-read | UserNotificationController@markAllRead | Exists | Wrapper only |
| Notifications | DELETE | /user/notifications/{id} | UserNotificationController@destroy | Exists | Wrapper only |
| Notifications | DELETE | /user/notifications | UserNotificationController@destroyAll | Exists | Wrapper only |
| Settings | GET | /user/settings/notifications | NotificationPreferencesController@show | Exists | Shape N1 |
| Settings | PUT | /user/settings/notifications | NotificationPreferencesController@update | Exists | Shape N1 |

## Vendor App (Android Vendor)

| Area | Method | Path | Controller | Status | Notes |
| --- | --- | --- | --- | --- | --- |
| Auth | POST | /auth/login | LoginController | Exists | Shape A1 |
| Auth | POST | /auth/register | RegisterController | Exists | Shape A2 |
| Auth | POST | /auth/refresh | RefreshTokenController | Exists | Shape A3 |
| Vendor Profile | POST | /user/account/setup-vendor-profile | UserController@setUpVendorProfile | Exists | Wrapper only |
| Vendor Profile | GET | /user/vendor/details | VendorController@getVendorDetails | Exists | Wrapper only |
| Vendor Profile | PUT | /user/vendor/details/update | VendorController@updateVendorDetails | Exists | Wrapper only |
| Products | GET | /user/vendor/product-categories | ProductCategoryController@index | Exists | Wrapper only |
| Products | POST | /user/vendor/product-categories | ProductCategoryController@store | Exists | Wrapper only |
| Products | POST | /user/vendor/product-categories/{id} | ProductCategoryController@update | Exists | Wrapper only |
| Products | DELETE | /user/vendor/product-categories/{id} | ProductCategoryController@delete | Exists | Wrapper only |
| Products | GET | /user/vendor/products | ProductController@index | Exists | Wrapper only |
| Products | POST | /user/vendor/products | ProductController@store | Exists | Wrapper only |
| Products | POST | /user/vendor/products/{id} | ProductController@update | Exists | Wrapper only |
| Products | DELETE | /user/vendor/products/{id} | ProductController@delete | Exists | Wrapper only |
| Orders | GET | /user/vendor/orders | VendorOrderController@index | Exists | Shape O1 |
| Orders | GET | /user/vendor/orders/{orderId} | VendorOrderController@show | Exists | Shape O2 |
| Orders | PUT | /user/vendor/orders/{orderId}/status | VendorOrderController@updateStatus | Exists | Wrapper only |
| Orders | GET | /user/vendor/orders/settlements/list | VendorOrderController@settlements | Exists | Wrapper only |
| Orders | GET | /user/vendor/orders/statistics/summary | VendorOrderController@orderStatisticsSummary | Exists | Wrapper only |
| Payments | GET | /user/vendor/payment-details | PaymentDetailsController@index | Exists | Wrapper only |
| Payments | GET | /user/vendor/payment-details/banks | PaymentDetailsController@listBanks | Exists | Wrapper only |
| Payments | POST | /user/vendor/payment-details/resolve-account | PaymentDetailsController@resolveAccount | Exists | Wrapper only |
| Payments | POST | /user/vendor/payment-details | PaymentDetailsController@store | Exists | Wrapper only |
| Payments | DELETE | /user/vendor/payment-details/{id} | PaymentDetailsController@destroy | Exists | Wrapper only |
| Promotions | GET | /user/vendor/promotions | VendorPromotionController@myPromotions | Exists | Wrapper only |
| Promotions | GET | /user/vendor/promotions/{id} | VendorPromotionController@show | Exists | Wrapper only |
| Promotions | POST | /user/vendor/promotions/request | VendorPromotionController@requestPromotion | Exists | Wrapper only |
| Promotions | DELETE | /user/vendor/promotions/{id} | VendorPromotionController@cancelRequest | Exists | Wrapper only |
| Subscriptions | GET | /user/vendor/subscriptions | SubscriptionController@fetchVendorSubscription | Exists | Wrapper only |
| Subscriptions | GET | /user/vendor/subscriptions/plans | SubscriptionController@getPlans | Exists | Wrapper only |
| Subscriptions | GET | /user/vendor/subscriptions/plans/{id} | SubscriptionController@fetchPlan | Exists | Wrapper only |
| Subscriptions | POST | /user/vendor/subscriptions/subscribe | SubscriptionController@subscribe | Exists | Wrapper only |
| Subscriptions | POST | /user/vendor/subscriptions/upgrade | SubscriptionController@upgradeSubscription | Exists | Wrapper only |
| Subscriptions | POST | /user/vendor/subscriptions/update-payment-method | SubscriptionController@updatePaymentMethod | Exists | Wrapper only |
| Subscriptions | POST | /user/vendor/subscriptions/cancel | SubscriptionController@cancelSubscription | Exists | Wrapper only |
| Earnings | GET | /user/vendor/earnings/summary | VendorEarningController@summary | Exists | Shape V1 |
| Payouts | GET | /user/vendor/payouts | VendorPayoutController@index | Exists | Stub |
| Payouts | POST | /user/vendor/payouts/withdraw | VendorPayoutController@withdraw | Exists | Stub |
| Store | PUT | /user/vendor/store/status | VendorStoreController@updateStatus | Exists | Stub (cache only) |
| Store | PUT | /user/vendor/store/hours | VendorStoreController@updateHours | Exists | Wrapper only |

## Admin Dashboard

| Area | Method | Path | Controller | Status | Notes |
| --- | --- | --- | --- | --- | --- |
| Auth | POST | /admin/auth/login | AdminLoginController | Exists | Shape A4 |
| Auth | POST | /admin/auth/send-code | AdminOtpController@send | Exists | Wrapper only |
| Auth | POST | /admin/auth/verify-code | AdminOtpController@verify | Exists | Wrapper only |
| Auth | POST | /admin/auth/reset-password | AdminResetPasswordController | Exists | Wrapper only |
| Health | GET | /admin/health | HealthController@index | Exists | Shape H1 |
| Alerts | GET | /admin/alerts/status | AlertStatusController@index | Exists | Wrapper only |
| Alerts | GET | /admin/alerts/summary | AlertSummaryController@index | Exists | Wrapper only |
| Alerts | GET | /admin/alerts/history | AlertHistoryController@index | Exists | Shape H2 |
| Audits | GET | /admin/audits | AuditLogController@index | Exists | Shape A5 |
| Notifications | POST | /admin/notifications/broadcast | BroadcastController | Exists | Stub |
| Notifications | GET | /admin/notifications | AdminNotificationController@index | Exists | Wrapper only |
| Notifications | GET | /admin/notifications/unread-count | AdminNotificationController@unreadCount | Exists | Wrapper only |
| Notifications | POST | /admin/notifications/{id}/read | AdminNotificationController@markRead | Exists | Wrapper only |
| Notifications | POST | /admin/notifications/mark-all-read | AdminNotificationController@markAllRead | Exists | Wrapper only |
| Notifications | DELETE | /admin/notifications/{id} | AdminNotificationController@destroy | Exists | Wrapper only |
| Notifications | DELETE | /admin/notifications | AdminNotificationController@destroyAll | Exists | Wrapper only |
| Users | GET | /admin/user-management | UserManagementController@index | Exists | Wrapper only |
| Users | GET | /admin/user-management/stats | UserManagementController@stats | Exists | Wrapper only |
| Users | POST | /admin/user-management | UserManagementController@store | Exists | Wrapper only |
| Users | GET | /admin/user-management/{id} | UserManagementController@show | Exists | Wrapper only |
| Users | PUT | /admin/user-management/{id} | UserManagementController@update | Exists | Wrapper only |
| Users | DELETE | /admin/user-management/{id} | UserManagementController@destroy | Exists | Wrapper only |
| Users | POST | /admin/user-management/{id}/suspend | UserManagementController@suspend | Exists | Wrapper only |
| Users | POST | /admin/user-management/{id}/activate | UserManagementController@activate | Exists | Wrapper only |
| Users | POST | /admin/user-management/{id}/change-status | UserManagementController@changeStatus | Exists | Wrapper only |
| Orders | GET | /admin/order-management | OrderManagementController@index | Exists | Shape O3 |
| Orders | GET | /admin/order-management/{id} | OrderManagementController@show | Exists | Shape O4 |
| Orders | GET | /admin/order-management/fetch/stats | OrderManagementController@stats | Exists | Wrapper only |
| Orders | POST | /admin/order-management/{id}/update-status | OrderManagementController@updateStatus | Exists | Wrapper only |
| Orders | POST | /admin/order-management/{id}/reassign | DriverManagementController@reassignOrder | Exists | Wrapper only |
| Drivers | GET | /admin/drivers | DriverManagementController@index | Exists | Wrapper only |
| Drivers | GET | /admin/drivers/locations | DriverManagementController@locations | Exists | Wrapper only |
| Drivers | GET | /admin/drivers/{id} | DriverManagementController@show | Exists | Wrapper only |
| Drivers | GET | /admin/drivers/{id}/stats | DriverManagementController@stats | Exists | Wrapper only |
| Drivers | POST | /admin/drivers/{id}/verify | DriverManagementController@verify | Exists | Wrapper only |
| Drivers | POST | /admin/drivers/{id}/block | DriverManagementController@block | Exists | Wrapper only |
| Drivers | POST | /admin/drivers/{id}/unblock | DriverManagementController@unblock | Exists | Wrapper only |
| Payouts | GET | /admin/payouts | DriverPayoutController@index | Exists | Wrapper only |
| Payouts | GET | /admin/payouts/export | DriverPayoutController@export | Exists | Wrapper only |
| Payouts | GET | /admin/payouts/reconcile | DriverPayoutController@reconcile | Exists | Wrapper only |
| Payouts | POST | /admin/payouts/{id}/approve | DriverPayoutController@approve | Exists | Wrapper only |
| Settings | GET | /admin/settings | AdminSettingsController@index | Exists | Wrapper only |
| Settings | POST | /admin/settings | AdminSettingsController@store | Exists | Wrapper only |
| Settings | GET | /admin/settings/{id} | AdminSettingsController@show | Exists | Wrapper only |
| Settings | PUT | /admin/settings/{id} | AdminSettingsController@update | Exists | Wrapper only |
| Settings | DELETE | /admin/settings/{id} | AdminSettingsController@destroy | Exists | Wrapper only |
| Settings | GET | /admin/settings/driver-app | DriverAppConfigController@show | Exists | Wrapper only |
| Settings | PUT | /admin/settings/driver-app | DriverAppConfigController@update | Exists | Wrapper only |
| Settings | GET | /admin/settings/commission | DriverCommissionController@show | Exists | Wrapper only |
| Settings | PUT | /admin/settings/commission | DriverCommissionController@update | Exists | Wrapper only |

## JSON Shapes

### A1. Auth Login (User/Vendor)
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "string",
    "refresh_token": "string",
    "role": "user|vendor|driver"
  }
}
```

### A2. Auth Register (User)
```json
{
  "success": true,
  "message": "Verify your email",
  "data": {
    "token": "string",
    "refresh_token": "string"
  }
}
```

### A3. Auth Refresh
```json
{
  "success": true,
  "message": "Token refreshed",
  "data": {
    "token": "string",
    "refresh_token": "string"
  }
}
```

### A4. Admin Login
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "name": "string",
    "email": "string",
    "role": "string",
    "is_super_admin": true,
    "token": "string"
  }
}
```

### O1. Orders List (User/Vendor)
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "data": ["OrderResource"],
    "next_cursor": "string|null",
    "prev_cursor": "string|null",
    "has_more": true,
    "per_page": 15
  }
}
```

### O2. Order Detail (User/Vendor)
```json
{
  "success": true,
  "message": "Order retrieved successfully",
  "data": "OrderResource"
}
```

### O3. Admin Orders List (Paginated)
```json
{
  "success": true,
  "message": "Orders retrieved successfully",
  "data": {
    "current_page": 1,
    "data": ["OrderModel"],
    "first_page_url": "string",
    "from": 1,
    "last_page": 1,
    "last_page_url": "string",
    "links": [],
    "next_page_url": "string|null",
    "path": "string",
    "per_page": 15,
    "prev_page_url": "string|null",
    "to": 1,
    "total": 1
  }
}
```

### O4. Admin Order Detail
```json
{
  "success": true,
  "message": "Order details retrieved successfully",
  "data": "OrderModel"
}
```

### T1. Order Tracking
```json
{
  "success": true,
  "message": "Order tracking retrieved successfully",
  "data": {
    "order_id": "uuid",
    "status": "string",
    "driver_id": "uuid|null",
    "driver_location": {
      "lat": 0,
      "lng": 0,
      "bearing": 0,
      "speed": 0,
      "accuracy": 0,
      "recorded_at": "datetime"
    },
    "delivery_location": {
      "lat": 0,
      "lng": 0
    },
    "updated_at": "datetime"
  }
}
```

### T2. Order ETA
```json
{
  "success": true,
  "message": "Order ETA retrieved successfully",
  "data": {
    "order_id": "uuid",
    "status": "string",
    "eta_minutes": 12,
    "updated_at": "datetime"
  }
}
```

### N1. Notification Preferences
```json
{
  "success": true,
  "message": "Notification preferences retrieved successfully",
  "data": {
    "push_in_app_notifications": true
  }
}
```

### V1. Vendor Earnings Summary
```json
{
  "success": true,
  "message": "Vendor earnings retrieved successfully",
  "data": {
    "total_orders": 0,
    "total_earnings": 0,
    "today_earnings": 0
  }
}
```

### H1. Admin Health
```json
{
  "success": true,
  "message": "Health check completed",
  "data": {
    "db": true,
    "cache": true,
    "queue_connection": "database",
    "time": "datetime"
  }
}
```

### H2. Alert History
```json
{
  "success": true,
  "message": "Alert history retrieved successfully",
  "data": {
    "data": [
      {
        "type": "notifications",
        "last_run": "datetime|null",
        "last_alert_at": "datetime|null",
        "last_total": 0,
        "last_failed": 0,
        "last_rate": 0
      }
    ]
  }
}
```

### A5. Audit Log List
```json
{
  "success": true,
  "message": "Audit logs retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [],
    "per_page": 20,
    "total": 0
  }
}
```
