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
    // Use emulator base URL if running on Android emulator in debug mode
    String baseUrl = const String.fromEnvironment(
      "API_BASE_URL",
      defaultValue: "https://shopittplus.espays.org/api/v1",
    );
    if (kDebugMode && Platform.isAndroid) {
      baseUrl = kEmulatorApiBaseUrl;
    }
    return AppConfig(
      apiBaseUrl: baseUrl,
      enableLogs: const bool.fromEnvironment("ENABLE_LOGS", defaultValue: false),
    );
  }
}
