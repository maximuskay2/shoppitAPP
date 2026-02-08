import "package:dio/dio.dart";

class RemoteConfigService {
  final Dio dio;
  RemoteConfigService(this.dio);

  Future<String?> fetchGoogleMapsApiKey() async {
    try {
      final response = await dio.get("/api/v1/admin/settings/maps-api-key");
      if (response.statusCode == 200 && response.data["success"] == true) {
        final setting = response.data["data"]["setting"];
        return setting != null ? setting["value"] as String? : null;
      }
    } catch (e) {
      // Handle error
    }
    return null;
  }

  Future<List<String>> fetchFcmTokens() async {
    try {
      final response = await dio.get("/api/v1/admin/settings/fcm-tokens");
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
