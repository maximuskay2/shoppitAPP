class DriverPayout {
  const DriverPayout({
    required this.id,
    required this.amount,
    required this.currency,
    required this.status,
    this.reference,
    this.paidAt,
    this.createdAt,
  });

  final String id;
  final num amount;
  final String currency;
  final String status;
  final String? reference;
  final String? paidAt;
  final String? createdAt;

  factory DriverPayout.fromJson(Map<String, dynamic> json) {
    return DriverPayout(
      id: json["id"].toString(),
      amount: json["amount"] is num
          ? json["amount"] as num
          : num.tryParse((json["amount"] ?? "0").toString()) ?? 0,
      currency: (json["currency"] ?? "").toString(),
      status: (json["status"] ?? "").toString(),
      reference: json["reference"]?.toString(),
      paidAt: json["paid_at"]?.toString(),
      createdAt: json["created_at"]?.toString(),
    );
  }
}

class PayoutBalance {
  const PayoutBalance({required this.withdrawable, required this.currency});

  final num withdrawable;
  final String currency;

  factory PayoutBalance.fromJson(Map<String, dynamic> json) {
    return PayoutBalance(
      withdrawable: json["withdrawable"] is num
          ? json["withdrawable"] as num
          : num.tryParse((json["withdrawable"] ?? "0").toString()) ?? 0,
      currency: (json["currency"] ?? "").toString(),
    );
  }
}
