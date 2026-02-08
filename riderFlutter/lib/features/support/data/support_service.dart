import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/support_ticket.dart";

class SupportService {
  SupportService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<List<SupportTicket>>> fetchTickets() async {
    final response = await _apiClient.dio.get(ApiPaths.supportTickets);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["data"] is List) {
          return (data["data"] as List)
              .map((item) => SupportTicket.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <SupportTicket>[];
      },
    );
  }

  Future<ApiResponse<SupportTicket?>> createTicket({
    required String subject,
    required String message,
    required String priority,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.supportTickets,
      data: {
        "subject": subject,
        "message": message,
        "priority": priority,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : SupportTicket.fromJson(data as Map<String, dynamic>),
    );
  }
}
