import "package:package_info_plus/package_info_plus.dart";
import "dart:io";

import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/app_bootstrap_config.dart";

class AppBootstrapService {
  AppBootstrapService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<AppBootstrapConfig>> fetchConfig() async {
    final info = await PackageInfo.fromPlatform();
    final response = await _apiClient.dio.get(
      ApiPaths.driverAppConfig,
      queryParameters: {
        "platform": Platform.operatingSystem,
        "version": info.version,
        "build": info.buildNumber,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : AppBootstrapConfig.fromJson(data as Map<String, dynamic>),
    );
  }

  bool isUpdateRequired({required String current, required String? minimum}) {
    if (minimum == null || minimum.isEmpty) return false;
    return _compareVersion(current, minimum) < 0;
  }

  int _compareVersion(String a, String b) {
    final aParts = a.split(".").map((e) => int.tryParse(e) ?? 0).toList();
    final bParts = b.split(".").map((e) => int.tryParse(e) ?? 0).toList();
    final maxLen = aParts.length > bParts.length ? aParts.length : bParts.length;

    for (var i = 0; i < maxLen; i++) {
      final aVal = i < aParts.length ? aParts[i] : 0;
      final bVal = i < bParts.length ? bParts[i] : 0;
      if (aVal != bVal) return aVal.compareTo(bVal);
    }
    return 0;
  }
}
