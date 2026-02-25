import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/payout_models.dart";

class PayoutService {
  PayoutService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<PayoutBalance>> fetchBalance() async {
    final response = await _apiClient.dio.get(ApiPaths.driverPayoutBalance);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : PayoutBalance.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<List<DriverPayout>>> fetchPayouts() async {
    final response = await _apiClient.dio.get(ApiPaths.driverPayouts);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is List) {
          return data
              .map((item) => DriverPayout.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <DriverPayout>[];
      },
    );
  }

  Future<ApiResponse<DriverPayout>> requestPayout() async {
    final response = await _apiClient.dio.post(ApiPaths.driverPayoutRequest);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverPayout.fromJson(data as Map<String, dynamic>),
    );
  }
}
