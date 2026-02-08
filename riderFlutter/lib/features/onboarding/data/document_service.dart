import "package:dio/dio.dart";

import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/driver_document.dart";

class DocumentService {
  DocumentService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<List<DriverDocument>>> fetchDocuments() async {
    final response = await _apiClient.dio.get(ApiPaths.driverDocuments);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is List) {
          return data
              .map((item) => DriverDocument.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <DriverDocument>[];
      },
    );
  }

  Future<ApiResponse<DriverDocument>> uploadDocument({
    required String documentType,
    required String filePath,
    String? expiresAt,
  }) async {
    final form = FormData.fromMap({
      "document_type": documentType,
      if (expiresAt != null && expiresAt.isNotEmpty) "expires_at": expiresAt,
      "document": await MultipartFile.fromFile(filePath),
    });

    final response = await _apiClient.dio.post(
      ApiPaths.driverDocuments,
      data: form,
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverDocument.fromJson(data as Map<String, dynamic>),
    );
  }
}
