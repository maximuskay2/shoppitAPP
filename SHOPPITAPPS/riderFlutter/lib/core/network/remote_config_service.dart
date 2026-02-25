import "package:dio/dio.dart";

import "api_paths.dart";

class RemoteConfigService {
  final Dio dio;
  RemoteConfigService(this.dio);

  /// Fetches Google Maps API key from driver app-config.
  /// Note: On Android/iOS, the native Maps SDK uses the key from the manifest
  /// at build time. This is for reference; ensure the key is in
  /// android/local.properties as GOOGLE_MAPS_API_KEY and rebuild the app.
  Future<String?> fetchGoogleMapsApiKey() async {
    try {
      final response = await dio.get(ApiPaths.driverAppConfig);
      if (response.statusCode == 200 && response.data["success"] == true) {
        final key = response.data["data"]?["google_maps_api_key"];
        return key != null && key.toString().isNotEmpty ? key.toString() : null;
      }
    } catch (e) {
      // Handle error
    }
    return null;
  }

  Future<List<String>> fetchFcmTokens() async {
    try {
      // Base URL already includes /api/v1, so path must not duplicate it
      final response = await dio.get("/admin/settings/fcm-tokens");
      if (response.statusCode == 200 && response.data["success"] == true) {
        final tokens = response.data["data"]["tokens"];
        if (tokens is List) {
          return tokens.map((t) => t.toString()).toList();
        }
      }
    } catch (e) {
      // Handle error
    }
    return [];
  }
}
