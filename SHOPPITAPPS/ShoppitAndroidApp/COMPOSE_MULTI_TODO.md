# Shoppit Compose Multiplatform Migration Progress 🎨

This document tracks the conversion of existing XML layouts to **Compose Multiplatform** inside the `shared` module. Once completed, these screens will work natively on both **Android** and **iOS**.

## 🏗 Setup & Infrastructure
- [x] **Configure Compose Multiplatform** in `shared/build.gradle.kts`.
- [x] **Setup Theme & Typography** (`shared/src/commonMain/kotlin/ui/theme`).
- [x] **Create UI Entry Point for iOS** ([`MainViewController.kt`](shared/src/iosMain/kotlin/com/shoppitplus/shoppit/shared/ui/MainViewController.kt)).

---

## 🟠 Phase 1: Authentication (CommonMain UI)
- [x] **Splash Screen** - Migrated to: [`SplashScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/SplashScreen.kt)
- [x] **Consumer Login** - Migrated to: [`LoginScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/LoginScreen.kt)
- [x] **Vendor Login** - Migrated to: [`VendorLoginScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorLoginScreen.kt)
- [x] **Consumer Registration** - Migrated to: [`RegisterScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/RegisterScreen.kt)
- [x] **Vendor Registration** - Migrated to: [`VendorRegisterScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorRegisterScreen.kt)
- [x] **Forgot Password** - Migrated to: [`ForgotPasswordScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/ForgotPasswordScreen.kt)

## 🔵 Phase 2: Consumer Discovery & Cart
- [x] **Home Screen** - Migrated to: [`HomeScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/HomeScreen.kt)
- [x] **Search Screen** - Migrated to: [`SearchScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/SearchScreen.kt)
- [x] **Product Detail Sheet** - Migrated to: [`ProductDetailScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/ProductDetailScreen.kt)
- [x] **Cart Screen** - Migrated to: [`CartScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/CartScreen.kt)
- [x] **Checkout Screen** - Migrated to: [`CheckoutScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/CheckoutScreen.kt)

## 🟣 Phase 3: Vendor Tools
- [x] **Vendor Dashboard** - Migrated to: [`VendorDashboardScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorDashboardScreen.kt)
- [x] **Vendor Product List** - Migrated to: [`VendorProductListScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorProductListScreen.kt)
- [x] **Add/Edit Product** - Migrated to: [`AddProductScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/AddProductScreen.kt)
- [x] **Vendor Orders** - Migrated to: [`VendorOrdersScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorOrdersScreen.kt)
- [x] **Order Detail** - Migrated to: [`VendorOrderDetailScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/VendorOrderDetailScreen.kt)

## 🟢 Phase 4: Account & Shared Features
- [x] **Order History (Consumer)** - Migrated to: [`OrderHistoryScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/OrderHistoryScreen.kt)
- [x] **User Profile** - Migrated to: [`ProfileScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/ProfileScreen.kt)
- [x] **Edit Profile** - Migrated to: [`EditProfileScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/EditProfileScreen.kt)
- [ ] **Wallet & Payouts** - Migrating to: [`WalletScreen.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/screens/WalletScreen.kt)
- [ ] **Notifications** - Currently at: [`Notification.kt`](app/src/main/java/com/shoppitplus/shoppit/notification/Notification.kt)

---

## 🛠 Shared UI Components (Atom Library)
- [x] **Shoppit Button** - Migrated to: [`ShoppitButton.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/components/ShoppitButton.kt)
- [x] **Shoppit TextField** - Migrated to: [`ShoppitTextField.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/components/ShoppitTextField.kt)
- [x] **Product Card** - Migrated to: [`ProductCard.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/components/ProductCard.kt)
- [x] **Vendor Card** - Migrated to: [`VendorCard.kt`](shared/src/commonMain/kotlin/com/shoppitplus/shoppit/shared/ui/components/VendorCard.kt)
- [ ] **Top Banner / Notification Snackbars**
