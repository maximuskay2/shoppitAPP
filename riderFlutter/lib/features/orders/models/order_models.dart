class MoneyAmount {
  const MoneyAmount({required this.amount, required this.currency});

  final num amount;
  final String currency;

  factory MoneyAmount.fromJson(Map<String, dynamic> json) {
    return MoneyAmount(
      amount: json["amount"] is num
          ? json["amount"] as num
          : num.tryParse((json["amount"] ?? "0").toString()) ?? 0,
      currency: (json["currency"] ?? "").toString(),
    );
  }

  factory MoneyAmount.fromValue(num amount, String currency) {
    return MoneyAmount(amount: amount, currency: currency);
  }
}

class OrderLineItem {
  const OrderLineItem({
    required this.id,
    required this.name,
    required this.quantity,
  });

  final String id;
  final String name;
  final int quantity;

  factory OrderLineItem.fromJson(Map<String, dynamic> json) {
    final product = json["product"] is Map<String, dynamic>
        ? json["product"] as Map<String, dynamic>
        : const <String, dynamic>{};
    return OrderLineItem(
      id: json["id"].toString(),
      name: (product["name"] ?? "Item").toString(),
      quantity: json["quantity"] is int
          ? json["quantity"] as int
          : int.tryParse((json["quantity"] ?? "0").toString()) ?? 0,
    );
  }
}

class VendorInfo {
  const VendorInfo({
    required this.id,
    required this.businessName,
    this.latitude,
    this.longitude,
    this.deliveryFee,
    this.city,
    this.state,
  });

  final String id;
  final String businessName;
  final double? latitude;
  final double? longitude;
  final MoneyAmount? deliveryFee;
  final String? city;
  final String? state;

  factory VendorInfo.fromJson(Map<String, dynamic> json) {
    return VendorInfo(
      id: json["id"].toString(),
      businessName: (json["business_name"] ?? "").toString(),
      latitude: (json["latitude"] as num?)?.toDouble(),
      longitude: (json["longitude"] as num?)?.toDouble(),
      deliveryFee: json["delivery_fee"] is Map<String, dynamic>
          ? MoneyAmount.fromJson(json["delivery_fee"] as Map<String, dynamic>)
          : (json["delivery_fee"] is num
              ? MoneyAmount.fromValue(
                  json["delivery_fee"] as num,
                  (json["currency"] ?? "").toString(),
                )
              : null),
      city: json["city"]?.toString(),
      state: json["state"]?.toString(),
    );
  }
}

class DriverOrder {
  const DriverOrder({
    required this.id,
    required this.status,
    required this.vendor,
    this.deliveryLatitude,
    this.deliveryLongitude,
    this.otpCode,
    this.receiverName,
    this.receiverPhone,
    this.grossTotal,
    this.netTotal,
    this.createdAt,
    this.lineItems = const [],
    this.zone,
    this.orderNotes,
  });

  final String id;
  final String status;
  final VendorInfo vendor;
  final double? deliveryLatitude;
  final double? deliveryLongitude;
  final String? otpCode;
  final String? receiverName;
  final String? receiverPhone;
  final MoneyAmount? grossTotal;
  final MoneyAmount? netTotal;
  final String? createdAt;
  final List<OrderLineItem> lineItems;
  final String? zone;
  /// Delivery / order instructions from customer (order_notes from API).
  final String? orderNotes;

  factory DriverOrder.fromJson(Map<String, dynamic> json) {
    final currency = (json["currency"] ?? "").toString();
    final vendor = VendorInfo.fromJson(json["vendor"] as Map<String, dynamic>);
    final zone = json["zone"]?.toString() ?? _formatZone(vendor);

    return DriverOrder(
      id: json["id"].toString(),
      status: (json["status"] ?? "").toString(),
      vendor: vendor,
      deliveryLatitude: (json["delivery_latitude"] as num?)?.toDouble(),
      deliveryLongitude: (json["delivery_longitude"] as num?)?.toDouble(),
      otpCode: json["otp_code"]?.toString(),
      receiverName: json["receiver_name"]?.toString(),
      receiverPhone: json["receiver_phone"]?.toString(),
      grossTotal: json["gross_total_amount"] is Map<String, dynamic>
          ? MoneyAmount.fromJson(
              json["gross_total_amount"] as Map<String, dynamic>,
            )
          : (json["gross_total_amount"] is num
              ? MoneyAmount.fromValue(
                  json["gross_total_amount"] as num,
                  currency,
                )
              : null),
      netTotal: json["net_total_amount"] is Map<String, dynamic>
          ? MoneyAmount.fromJson(
              json["net_total_amount"] as Map<String, dynamic>,
            )
          : (json["net_total_amount"] is num
              ? MoneyAmount.fromValue(
                  json["net_total_amount"] as num,
                  currency,
                )
              : null),
      createdAt: json["created_at"]?.toString(),
      lineItems: json["line_items"] is List
          ? (json["line_items"] as List)
              .map((item) => OrderLineItem.fromJson(item as Map<String, dynamic>))
              .toList()
          : const [],
      zone: zone,
      orderNotes: json["order_notes"]?.toString(),
    );
  }
}

String? _formatZone(VendorInfo vendor) {
  final city = vendor.city;
  final state = vendor.state;
  if (city == null && state == null) return null;
  if (city != null && state != null && city.isNotEmpty && state.isNotEmpty) {
    return "$city, $state";
  }
  return (city?.isNotEmpty ?? false) ? city : state;
}
