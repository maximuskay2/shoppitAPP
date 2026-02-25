import "package:dio/dio.dart";

import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../../../core/network/api_client.dart";
import "../models/order_models.dart";

class OrderService {
  OrderService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<List<DriverOrder>>> fetchAvailableOrders({
    double? latitude,
    double? longitude,
    String? vendorId,
  }) async {
    final response = await _apiClient.dio.get(
      ApiPaths.availableOrders,
      queryParameters: {
        if (latitude != null) "latitude": latitude,
        if (longitude != null) "longitude": longitude,
        if (vendorId != null && vendorId.isNotEmpty) "vendor_id": vendorId,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["data"] is List) {
          return (data["data"] as List)
              .map((item) => DriverOrder.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <DriverOrder>[];
      },
    );
  }

  Future<ApiResponse<List<DriverOrder>>> fetchActiveOrders() async {
    final response = await _apiClient.dio.get(ApiPaths.activeOrders);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["data"] is List) {
          return (data["data"] as List)
              .map((item) => DriverOrder.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        if (data is Map<String, dynamic>) {
          return [DriverOrder.fromJson(data)];
        }
        return <DriverOrder>[];
      },
    );
  }

  Future<ApiResponse<List<DriverOrder>>> fetchOrderHistory() async {
    final response = await _apiClient.dio.get(ApiPaths.orderHistory);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["data"] is List) {
          return (data["data"] as List)
              .map((item) => DriverOrder.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <DriverOrder>[];
      },
    );
  }

  Future<ApiResponse<void>> acceptOrder(String orderId) async {
    final response = await _apiClient.dio.post(ApiPaths.acceptOrder(orderId));
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> rejectOrder(String orderId) async {
    final response = await _apiClient.dio.post(ApiPaths.rejectOrder(orderId));
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> pickupOrder(String orderId) async {
    final response = await _apiClient.dio.post(ApiPaths.pickupOrder(orderId));
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> startDelivery(String orderId) async {
    final response = await _apiClient.dio.post(ApiPaths.outForDelivery(orderId));
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> deliverOrder(
    String orderId, {
    String? otpCode,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.deliverOrder(orderId),
      data: {
        if (otpCode != null && otpCode.isNotEmpty) "otp_code": otpCode,
      },
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> uploadProofOfDelivery(
    String orderId, {
    String? photoPath,
    String? signaturePath,
  }) async {
    final form = FormData.fromMap({
      if (photoPath != null && photoPath.isNotEmpty)
        "photo": await MultipartFile.fromFile(photoPath),
      if (signaturePath != null && signaturePath.isNotEmpty)
        "signature": await MultipartFile.fromFile(signaturePath),
    });

    final response = await _apiClient.dio.post(
      ApiPaths.uploadPod(orderId),
      data: form,
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> cancelOrder(
    String orderId, {
    required String reason,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.cancelOrder(orderId),
      data: {
        "reason": reason,
      },
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }
}
