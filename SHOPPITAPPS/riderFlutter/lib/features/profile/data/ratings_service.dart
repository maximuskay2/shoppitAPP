import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/driver_rating.dart";

class RatingsService {
  RatingsService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<DriverRatingSummary>> fetchRatings() async {
    final response = await _apiClient.dio.get(ApiPaths.driverRatings);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverRatingSummary.fromJson(data as Map<String, dynamic>),
    );
  }
}
