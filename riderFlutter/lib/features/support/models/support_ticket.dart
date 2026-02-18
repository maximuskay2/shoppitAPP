class SupportTicket {
  const SupportTicket({
    required this.id,
    required this.subject,
    required this.message,
    required this.priority,
    required this.status,
    this.createdAt,
    this.updatedAt,
    this.resolvedAt,
    this.meta,
  });

  final String id;
  final String subject;
  final String message;
  final String priority;
  final String status;
  final String? createdAt;
  final String? updatedAt;
  final String? resolvedAt;
  final Map<String, dynamic>? meta;

  factory SupportTicket.fromJson(Map<String, dynamic> json) {
    return SupportTicket(
      id: json["id"].toString(),
      subject: (json["subject"] ?? "").toString(),
      message: (json["message"] ?? "").toString(),
      priority: (json["priority"] ?? "NORMAL").toString(),
      status: (json["status"] ?? "OPEN").toString(),
      createdAt: json["created_at"]?.toString(),
      updatedAt: json["updated_at"]?.toString(),
      resolvedAt: json["resolved_at"]?.toString(),
      meta: json["meta"] is Map<String, dynamic>
          ? json["meta"] as Map<String, dynamic>
          : null,
    );
  }
}
