import "package:flutter/widgets.dart";

import "../config/app_config.dart";
import "../core/location/location_tracker.dart";
import "../core/notifications/notification_service.dart";
import "../core/network/api_client.dart";
import "../core/storage/token_storage.dart";

class AppDependencies {
  const AppDependencies({
    required this.config,
    required this.apiClient,
    required this.tokenStorage,
    required this.locationTracker,
    required this.notificationService,
  });

  final AppConfig config;
  final ApiClient apiClient;
  final TokenStorage tokenStorage;
  final LocationTracker locationTracker;
  final NotificationService notificationService;
}

class AppScope extends InheritedWidget {
  const AppScope({
    super.key,
    required this.dependencies,
    required super.child,
  });

  final AppDependencies dependencies;

  static AppDependencies of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AppScope>();
    if (scope == null) {
      throw StateError("AppScope not found in widget tree.");
    }
    return scope.dependencies;
  }

  @override
  bool updateShouldNotify(covariant AppScope oldWidget) {
    return false;
  }
}
