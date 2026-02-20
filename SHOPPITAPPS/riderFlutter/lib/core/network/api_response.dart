class ApiResponse<T> {
  const ApiResponse({
    required this.success,
    required this.message,
    required this.statusCode,
    this.data,
    this.fieldErrors = const {},
  });

  final bool success;
  final String message;
  final int statusCode;
  final T? data;
  final Map<String, String> fieldErrors;

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T? Function(dynamic data) parser,
  ) {
    final success = json["success"] == true;
    final data = json["data"];
    final fieldErrors = success ? <String, String>{} : _parseFieldErrors(data);

    return ApiResponse(
      success: success,
      message: (json["message"] ?? "").toString(),
      statusCode: json["statusCode"] is int
          ? json["statusCode"] as int
          : int.tryParse((json["statusCode"] ?? "").toString()) ?? 0,
      data: success && json.containsKey("data") ? parser(data) : null,
      fieldErrors: fieldErrors,
    );
  }
}

Map<String, String> _parseFieldErrors(dynamic data) {
  if (data is! Map<String, dynamic>) return {};

  final errors = <String, String>{};
  for (final entry in data.entries) {
    final value = entry.value;
    if (value is List && value.isNotEmpty) {
      errors[entry.key] = value.first.toString();
    } else if (value is String && value.isNotEmpty) {
      errors[entry.key] = value;
    }
  }

  return errors;
}
