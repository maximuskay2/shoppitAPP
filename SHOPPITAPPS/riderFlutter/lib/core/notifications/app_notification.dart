import 'package:meta/meta.dart';

/// Unified notification model for both API and FCM payloads.
@immutable
class AppNotification {
  final String id;
  final String type;
  final String notifiableType;
  final String notifiableId;
  final String title;
  final String body;
  final NotificationData data;
  final DateTime? readAt;
  final DateTime createdAt;
  final DateTime updatedAt;

  const AppNotification({
    required this.id,
    required this.type,
    required this.notifiableType,
    required this.notifiableId,
    required this.title,
    required this.body,
    required this.data,
    required this.readAt,
    required this.createdAt,
    required this.updatedAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as String,
      type: json['type'] as String,
      notifiableType: json['notifiable_type'] as String,
      notifiableId: json['notifiable_id'] as String,
      title: json['title'] as String,
      body: json['body'] as String,
      data: NotificationData.fromJson(json['data'] as Map<String, dynamic>),
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'type': type,
        'notifiable_type': notifiableType,
        'notifiable_id': notifiableId,
        'title': title,
        'body': body,
        'data': data.toJson(),
        'read_at': readAt?.toIso8601String(),
        'created_at': createdAt.toIso8601String(),
        'updated_at': updatedAt.toIso8601String(),
      };
}

class NotificationData {
  final String? orderId;
  final String? trackingId;
  final String? customerName;
  final int? amount;
  final String? currency;
  final int? itemsCount;
  final String? settlementId;
  final int? vendorAmount;
  final int? platformFee;

  const NotificationData({
    this.orderId,
    this.trackingId,
    this.customerName,
    this.amount,
    this.currency,
    this.itemsCount,
    this.settlementId,
    this.vendorAmount,
    this.platformFee,
  });

  factory NotificationData.fromJson(Map<String, dynamic> json) {
    return NotificationData(
      orderId: json['order_id'] as String?,
      trackingId: json['tracking_id'] as String?,
      customerName: json['customer_name'] as String?,
      amount: json['amount'] as int?,
      currency: json['currency'] as String?,
      itemsCount: json['items_count'] as int?,
      settlementId: json['settlement_id'] as String?,
      vendorAmount: json['vendor_amount'] as int?,
      platformFee: json['platform_fee'] as int?,
    );
  }

  Map<String, dynamic> toJson() => {
        'order_id': orderId,
        'tracking_id': trackingId,
        'customer_name': customerName,
        'amount': amount,
        'currency': currency,
        'items_count': itemsCount,
        'settlement_id': settlementId,
        'vendor_amount': vendorAmount,
        'platform_fee': platformFee,
      };
}
