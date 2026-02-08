import "package:dio/dio.dart";

import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../../../core/network/api_client.dart";
import "../../../core/storage/token_storage.dart";
import "../models/auth_models.dart";

class AuthService {
  AuthService({required ApiClient apiClient, required TokenStorage tokenStorage})
      : _apiClient = apiClient,
        _tokenStorage = tokenStorage;

  final ApiClient _apiClient;
  final TokenStorage _tokenStorage;

  Future<ApiResponse<AuthResult>> login(LoginRequest request) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverLogin,
      data: request.toJson(),
    );

    final parsed = ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : AuthResult.fromJson(data as Map<String, dynamic>),
    );

    if (parsed.success && parsed.data?.token.isNotEmpty == true) {
      await _tokenStorage.saveToken(parsed.data!.token);
    }

    return parsed;
  }

  Future<ApiResponse<AuthResult>> register(RegisterRequest request) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverRegister,
      data: request.toJson(),
    );

    final parsed = ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : AuthResult.fromJson(data as Map<String, dynamic>),
    );

    if (parsed.success && parsed.data?.token.isNotEmpty == true) {
      await _tokenStorage.saveToken(parsed.data!.token);
    }

    return parsed;
  }

  Future<ApiResponse<AuthResult>> loginWithOtp(OtpLoginRequest request) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverLoginOtp,
      data: request.toJson(),
    );

    final parsed = ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : AuthResult.fromJson(data as Map<String, dynamic>),
    );

    if (parsed.success && parsed.data?.token.isNotEmpty == true) {
      await _tokenStorage.saveToken(parsed.data!.token);
    }

    return parsed;
  }

  Future<void> logout() async {
    await _tokenStorage.clearToken();
  }

  Future<ApiResponse<void>> sendOtp(OtpSendRequest request) async {
    final response = await _apiClient.dio.post(
      ApiPaths.authSendOtp,
      data: request.toJson(),
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> verifyOtp(OtpVerifyRequest request) async {
    final response = await _apiClient.dio.post(
      ApiPaths.authVerifyOtp,
      data: request.toJson(),
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> verifyRegisterOtp(
    RegisterOtpVerifyRequest request,
  ) async {
    final response = await _apiClient.dio.post(
      ApiPaths.authVerifyRegisterOtp,
      data: request.toJson(),
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<ApiResponse<void>> resendRegisterOtp(
    RegisterOtpResendRequest request,
  ) async {
    final response = await _apiClient.dio.post(
      ApiPaths.authResendRegisterOtp,
      data: request.toJson(),
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }

  Future<String?> getToken() {
    return _tokenStorage.readToken();
  }
}
