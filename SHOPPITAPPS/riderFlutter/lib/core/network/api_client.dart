import "package:dio/dio.dart";
import "package:flutter/foundation.dart";

import "../storage/token_storage.dart";

class ApiClient {
  ApiClient({
    required TokenStorage tokenStorage,
    required String baseUrl,
    bool enableLogs = false,
  })  : _tokenStorage = tokenStorage,
        _dio = Dio(
          BaseOptions(
            baseUrl: baseUrl,
            headers: const {"Accept": "application/json"},
            connectTimeout: const Duration(seconds: 20),
            receiveTimeout: const Duration(seconds: 20),
            validateStatus: (status) => status != null && status < 500,
          ),
        ) {
    _dio.interceptors.add(_AuthInterceptor(_tokenStorage));
    _dio.interceptors.add(_JsonErrorInterceptor());
    if (enableLogs) {
      _dio.interceptors.add(
        LogInterceptor(
          requestBody: true,
          responseBody: true,
          logPrint: (message) => debugPrint(message.toString()),
        ),
      );
    }
  }

  final Dio _dio;
  final TokenStorage _tokenStorage;

  Dio get dio => _dio;
}

class _AuthInterceptor extends Interceptor {
  _AuthInterceptor(this._tokenStorage);

  final TokenStorage _tokenStorage;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _tokenStorage.readToken();
    if (token != null && token.isNotEmpty) {
      options.headers["Authorization"] = "Bearer $token";
    }
    handler.next(options);
  }
}

/// When the server returns HTML (e.g. 404/500 from Apache) instead of JSON,
/// Dio throws FormatException. Surface a clear message so the user knows to
/// run the Laravel API (e.g. php artisan serve) and use the correct base URL.
class _JsonErrorInterceptor extends Interceptor {
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    final message = err.message ?? '';
    final isJsonParseError = message.contains('FormatException') ||
        message.contains('Unexpected character');
    if (isJsonParseError && err.requestOptions.uri.toString().contains('api')) {
      handler.reject(
        DioException(
          requestOptions: err.requestOptions,
          error: err.error,
          type: DioExceptionType.badResponse,
          message: 'Server returned invalid response (not JSON). '
              'Ensure the Laravel API is running. '
              'For emulator: run "php artisan serve" and use base URL http://10.0.2.2:8000/api/v1',
        ),
      );
      return;
    }
    handler.next(err);
  }
}
