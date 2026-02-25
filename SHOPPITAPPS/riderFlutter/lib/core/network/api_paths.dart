class ApiPaths {
  static const authSendOtp = "/auth/send-code";
  static const authVerifyOtp = "/auth/verify-code";
  static const authResendRegisterOtp = "/auth/resend-register-otp";
  static const authVerifyRegisterOtp = "/auth/verify-register-otp";

  static const driverRegister = "/driver/auth/register";
  static const driverLogin = "/driver/auth/login";
  static const driverLoginOtp = "/driver/auth/login-otp";
  static const driverProfile = "/driver/profile";
  static const driverProfileAvatar = "/driver/profile/avatar";
  static const driverProfilePassword = "/driver/profile/password";
  static const driverVehicles = "/driver/vehicles";
  static const driverStatus = "/driver/status";
  static const driverFcmToken = "/driver/fcm-token";
  static const driverDocuments = "/driver/documents";
  static const driverAppConfig = "/driver/app-config";

  static const availableOrders = "/driver/orders/available";
  static const activeOrders = "/driver/orders/active";
  static const orderHistory = "/driver/orders/history";

  static String acceptOrder(String orderId) => "/driver/orders/$orderId/accept";
  static String rejectOrder(String orderId) => "/driver/orders/$orderId/reject";
  static String pickupOrder(String orderId) => "/driver/orders/$orderId/pickup";
  static String outForDelivery(String orderId) =>
      "/driver/orders/$orderId/out-for-delivery";
  static String deliverOrder(String orderId) => "/driver/orders/$orderId/deliver";
  static String uploadPod(String orderId) => "/driver/orders/$orderId/pod";
  static String cancelOrder(String orderId) => "/driver/orders/$orderId/cancel";

  static const driverStats = "/driver/stats";
  static const driverRatings = "/driver/ratings";
  static const driverEarnings = "/driver/earnings";
  static const driverEarningsHistory = "/driver/earnings/history";
  static const driverPayouts = "/driver/payouts";
  static const driverPayoutBalance = "/driver/payouts/balance";
  static const driverPayoutRequest = "/driver/payouts/request";

  static const driverPaymentDetails = "/driver/payment-details";
  static const driverPaymentBanks = "/driver/payment-details/banks";
  static const driverPaymentResolve = "/driver/payment-details/resolve-account";
  static const driverSos = "/driver/sos";

  static const driverLocation = "/driver/location";
  static const driverLocationUpdate = "/driver/location-update";

  static const supportTickets = "/driver/support/tickets";
  static const navigationRoute = "/driver/navigation/route";

  static const driverMessaging = "/driver/messaging";
  static const driverMessagingConversations = "/driver/messaging/conversations";
  static const driverMessagingConversationsAdmin = "/driver/messaging/conversations/admin";
  static String driverMessagingMessages(String id) => "/driver/messaging/conversations/$id/messages";
  // Unified Notification Endpoints
  static const unifiedNotifications = "/driver/notifications/unified";
  static String unifiedMarkRead(String id) =>
      "/driver/notifications/unified/$id/read";
  static String unifiedMarkUnread(String id) =>
      "/driver/notifications/unified/$id/unread";
  static const unifiedSend = "/driver/notifications/unified/send";

}
