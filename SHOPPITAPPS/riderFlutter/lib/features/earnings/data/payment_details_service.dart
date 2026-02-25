import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/payment_details_models.dart";

class PaymentDetailsService {
  PaymentDetailsService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<DriverPaymentDetail?>> fetchPaymentDetail() async {
    final response = await _apiClient.dio.get(ApiPaths.driverPaymentDetails);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverPaymentDetail.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<List<BankInfo>>> fetchBanks() async {
    final response = await _apiClient.dio.get(ApiPaths.driverPaymentBanks);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is List) {
          return data
              .map((item) => BankInfo.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <BankInfo>[];
      },
    );
  }

  Future<ApiResponse<Map<String, dynamic>>> resolveAccount({
    required String accountNumber,
    required String bankCode,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverPaymentResolve,
      data: {
        "account_number": accountNumber,
        "bank_code": bankCode,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data is Map<String, dynamic> ? data : <String, dynamic>{},
    );
  }

  Future<ApiResponse<DriverPaymentDetail?>> savePaymentDetail({
    required String accountNumber,
    required String bankCode,
    required String accountName,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverPaymentDetails,
      data: {
        "account_number": accountNumber,
        "bank_code": bankCode,
        "account_name": accountName,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverPaymentDetail.fromJson(data as Map<String, dynamic>),
    );
  }
}
