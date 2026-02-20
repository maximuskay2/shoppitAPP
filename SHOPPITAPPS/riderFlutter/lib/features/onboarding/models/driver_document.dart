class DriverDocument {
  const DriverDocument({
    required this.id,
    required this.documentType,
    required this.fileUrl,
    required this.status,
    this.expiresAt,
    this.rejectedAt,
    this.rejectionReason,
  });

  final String id;
  final String documentType;
  final String fileUrl;
  final String status;
  final String? expiresAt;
  final String? rejectedAt;
  final String? rejectionReason;

  factory DriverDocument.fromJson(Map<String, dynamic> json) {
    return DriverDocument(
      id: json["id"].toString(),
      documentType: (json["document_type"] ?? "").toString(),
      fileUrl: (json["file_url"] ?? "").toString(),
      status: (json["status"] ?? "").toString(),
      expiresAt: json["expires_at"]?.toString(),
      rejectedAt: json["rejected_at"]?.toString(),
      rejectionReason: json["rejection_reason"]?.toString(),
    );
  }
}
