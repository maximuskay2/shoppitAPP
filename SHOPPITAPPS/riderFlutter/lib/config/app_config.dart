import 'dart:io';
import 'package:flutter/foundation.dart';
import 'emulator_api_url.dart';
class AppConfig {
  const AppConfig({
    required this.apiBaseUrl,
    required this.enableLogs,
  });

  final String apiBaseUrl;
  final bool enableLogs;

  factory AppConfig.fromEnv() {
    // API_BASE_URL can be set via run_dev.sh (e.g. run_dev.sh android) or --dart-define.
    // If not set and running on Android emulator in debug, use XAMPP URL (no artisan serve needed).
    const String productionUrl = "https://laravelapi-production-1ea4.up.railway.app/api/v1";
    String baseUrl = const String.fromEnvironment(
      "API_BASE_URL",
      defaultValue: productionUrl,
    );
    if (kDebugMode && Platform.isAndroid && baseUrl == productionUrl) {
      baseUrl = kEmulatorApiBaseUrl;
    }
    return AppConfig(
      apiBaseUrl: baseUrl,
      enableLogs: const bool.fromEnvironment("ENABLE_LOGS", defaultValue: false),
    );
  }
}
