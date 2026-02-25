import "package:firebase_core/firebase_core.dart";
import "package:flutter/widgets.dart";

import "app/app.dart";
import "app/app_scope.dart";
import "config/app_config.dart";
import "core/location/location_tracker.dart";
import "core/notifications/notification_service.dart";
import "core/network/api_client.dart";
import "core/storage/token_storage.dart";

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  var firebaseReady = false;
  try {
    await Firebase.initializeApp();
    firebaseReady = true;
  } catch (_) {
    // Firebase init can be skipped in local dev if not configured.
  }

  final config = AppConfig.fromEnv();
  final tokenStorage = TokenStorage();
  final apiClient = ApiClient(
    tokenStorage: tokenStorage,
    baseUrl: config.apiBaseUrl,
    enableLogs: config.enableLogs,
  );
  final locationTracker = LocationTracker(apiClient: apiClient);
  final navigatorKey = GlobalKey<NavigatorState>();
  final notificationService = NotificationService(
    apiClient: apiClient,
    navigatorKey: navigatorKey,
  );

  if (firebaseReady) {
    await notificationService.initialize();
  }

  runApp(
    App(
      dependencies: AppDependencies(
        config: config,
        apiClient: apiClient,
        tokenStorage: tokenStorage,
        locationTracker: locationTracker,
        notificationService: notificationService,
      ),
      navigatorKey: navigatorKey,
    ),
  );
}
