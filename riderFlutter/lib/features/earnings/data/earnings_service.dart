import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/earnings_models.dart";

class EarningsService {
  EarningsService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<EarningsSummary>> fetchSummary() async {
    final response = await _apiClient.dio.get(ApiPaths.driverEarnings);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : EarningsSummary.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<List<EarningsHistoryItem>>> fetchHistory({
    int perPage = 20,
  }) async {
    final response = await _apiClient.dio.get(
      ApiPaths.driverEarningsHistory,
      queryParameters: {"per_page": perPage},
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["data"] is List) {
          return (data["data"] as List)
              .map((item) =>
                  EarningsHistoryItem.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <EarningsHistoryItem>[];
      },
    );
  }

  Future<ApiResponse<DriverStats>> fetchStats() async {
    final response = await _apiClient.dio.get(ApiPaths.driverStats);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverStats.fromJson(data as Map<String, dynamic>),
    );
  }
}
