import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/route_models.dart";

class NavigationService {
  NavigationService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<RouteInfo>> fetchRoute({
    required double originLat,
    required double originLng,
    required double destinationLat,
    required double destinationLng,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.navigationRoute,
      data: {
        "origin_lat": originLat,
        "origin_lng": originLng,
        "destination_lat": destinationLat,
        "destination_lng": destinationLng,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : RouteInfo.fromJson(data as Map<String, dynamic>),
    );
  }
}
