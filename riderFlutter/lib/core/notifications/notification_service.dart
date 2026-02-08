import "dart:async";

import "package:firebase_messaging/firebase_messaging.dart";
import "package:flutter/material.dart";
import "package:flutter_local_notifications/flutter_local_notifications.dart";
import '../../features/home/presentation/home_shell.dart';
import "../network/api_client.dart";
import "../network/api_paths.dart";
import "../../features/home/presentation/home_shell.dart";

class NotificationService {
  NotificationService({
    required ApiClient apiClient,
    required GlobalKey<NavigatorState> navigatorKey,
  })  : _apiClient = apiClient,
        _navigatorKey = navigatorKey;

  final ApiClient _apiClient;
  final GlobalKey<NavigatorState> _navigatorKey;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  static const _channelId = "driver_updates";
  static const _channelName = "Driver updates";

  Future<void> initialize() async {
    await _requestPermissions();
    await _configureLocalNotifications();

    FirebaseMessaging.onMessage.listen(_onForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleMessageTap);

    final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
    if (initialMessage != null) {
      _handleMessageTap(initialMessage);
    }

    await registerFcmTokenIfNeeded();
  }

  Future<void> registerFcmTokenIfNeeded() async {
    try {
      final token = await FirebaseMessaging.instance.getToken();
      if (token == null || token.isEmpty) return;

      await _apiClient.dio.post(
        ApiPaths.driverFcmToken,
        data: {"fcm_device_token": token},
      );
    } catch (_) {
      // Token registration failures should not crash app startup.
    }
  }

  Future<void> _requestPermissions() async {
    await FirebaseMessaging.instance.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    );
  }

  Future<void> _configureLocalNotifications() async {
    const android = AndroidInitializationSettings("@mipmap/ic_launcher");
    const ios = DarwinInitializationSettings();
    const settings = InitializationSettings(android: android, iOS: ios);

    await _localNotifications.initialize(
      settings,
      onDidReceiveNotificationResponse: (response) {
        final orderId = response.payload;
        if (orderId != null && orderId.isNotEmpty) {
          _navigateToOrder(orderId);
        }
      },
    );

    const channel = AndroidNotificationChannel(
      _channelId,
      _channelName,
      description: "Order updates and assignments",
      importance: Importance.high,
    );

    await _localNotifications
        .resolvePlatformSpecificImplementation<
            AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);
  }

  Future<void> _onForegroundMessage(RemoteMessage message) async {
    final title = message.notification?.title ?? "Driver update";
    final body = message.notification?.body ?? "You have a new update";
    final orderId = _extractOrderId(message);

    final details = NotificationDetails(
      android: AndroidNotificationDetails(
        _channelId,
        _channelName,
        importance: Importance.high,
        priority: Priority.high,
      ),
      iOS: const DarwinNotificationDetails(),
    );

    await _localNotifications.show(
      DateTime.now().millisecondsSinceEpoch ~/ 1000,
      title,
      body,
      details,
      payload: orderId,
    );
  }

  void _handleMessageTap(RemoteMessage message) {
    final orderId = _extractOrderId(message);
    if (orderId != null) {
      _navigateToOrder(orderId);
    } else {
      _navigateToHome();
    }
  }

  String? _extractOrderId(RemoteMessage message) {
    final data = message.data;
    if (data.containsKey("order_id")) return data["order_id"]?.toString();
    if (data.containsKey("orderId")) return data["orderId"]?.toString();
    return null;
  }

  void _navigateToHome() {
    _navigatorKey.currentState?.pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const HomeShell()),
      (route) => false,
    );
  }

  void _navigateToOrder(String orderId) {
    _navigatorKey.currentState?.pushAndRemoveUntil(
      MaterialPageRoute(
        builder: (_) => HomeShell(
          initialIndex: 0,
          highlightOrderId: orderId,
        ),
      ),
      (route) => false,
    );
  }
}
