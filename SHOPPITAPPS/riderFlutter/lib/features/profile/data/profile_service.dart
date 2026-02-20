import "package:dio/dio.dart";

import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../../../core/network/api_client.dart";
import "../models/driver_profile.dart";

class ProfileService {
  ProfileService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<DriverProfile>> fetchProfile() async {
    final response = await _apiClient.dio.get(ApiPaths.driverProfile);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverProfile.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<DriverProfile>> updateProfile(
    DriverProfileUpdateRequest request,
  ) async {
    final response = await _apiClient.dio.put(
      ApiPaths.driverProfile,
      data: request.toJson(),
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverProfile.fromJson(data as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<bool>> updateStatus({required bool isOnline}) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverStatus,
      data: {"is_online": isOnline},
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null ? null : (data["is_online"] == true),
    );
  }

  Future<ApiResponse<void>> registerFcmToken(String token) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverFcmToken,
      data: {"fcm_device_token": token},
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> updateAvatar(String filePath) async {
    final form = FormData.fromMap({
      "avatar": await MultipartFile.fromFile(filePath),
    });

    final response = await _apiClient.dio.post(
      ApiPaths.driverProfileAvatar,
      data: form,
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverProfilePassword,
      data: {
        "current_password": currentPassword,
        "password": newPassword,
        "password_confirmation": confirmPassword,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }
}
