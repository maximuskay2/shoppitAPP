class EarningsSummary {
  const EarningsSummary({
    required this.currency,
    required this.totals,
    required this.byStatus,
    required this.counts,
  });

  final String currency;
  final EarningsTotals totals;
  final EarningsByStatus byStatus;
  final EarningsCounts counts;

  factory EarningsSummary.fromJson(Map<String, dynamic> json) {
    return EarningsSummary(
      currency: (json["currency"] ?? "").toString(),
      totals: EarningsTotals.fromJson(
        json["totals"] as Map<String, dynamic>,
      ),
      byStatus: EarningsByStatus.fromJson(
        json["by_status"] as Map<String, dynamic>,
      ),
      counts: EarningsCounts.fromJson(
        json["counts"] as Map<String, dynamic>,
      ),
    );
  }
}

class EarningsTotals {
  const EarningsTotals({
    required this.gross,
    required this.commission,
    required this.net,
  });

  final num gross;
  final num commission;
  final num net;

  factory EarningsTotals.fromJson(Map<String, dynamic> json) {
    return EarningsTotals(
      gross: _toNum(json["gross"]),
      commission: _toNum(json["commission"]),
      net: _toNum(json["net"]),
    );
  }
}

class EarningsByStatus {
  const EarningsByStatus({required this.pending, required this.paid});

  final num pending;
  final num paid;

  factory EarningsByStatus.fromJson(Map<String, dynamic> json) {
    return EarningsByStatus(
      pending: _toNum(json["pending"]),
      paid: _toNum(json["paid"]),
    );
  }
}

class EarningsCounts {
  const EarningsCounts({
    required this.total,
    required this.pending,
    required this.paid,
  });

  final int total;
  final int pending;
  final int paid;

  factory EarningsCounts.fromJson(Map<String, dynamic> json) {
    return EarningsCounts(
      total: _toInt(json["total"]),
      pending: _toInt(json["pending"]),
      paid: _toInt(json["paid"]),
    );
  }
}

class EarningsHistoryItem {
  const EarningsHistoryItem({
    required this.id,
    required this.orderId,
    required this.trackingId,
    required this.grossAmount,
    required this.commissionAmount,
    required this.netAmount,
    required this.currency,
    required this.status,
    required this.payoutId,
    required this.createdAt,
  });

  final String id;
  final String? orderId;
  final String? trackingId;
  final num grossAmount;
  final num commissionAmount;
  final num netAmount;
  final String currency;
  final String status;
  final String? payoutId;
  final String? createdAt;

  factory EarningsHistoryItem.fromJson(Map<String, dynamic> json) {
    return EarningsHistoryItem(
      id: json["id"].toString(),
      orderId: json["order_id"]?.toString(),
      trackingId: json["tracking_id"]?.toString(),
      grossAmount: _toNum(json["gross_amount"]),
      commissionAmount: _toNum(json["commission_amount"]),
      netAmount: _toNum(json["net_amount"]),
      currency: (json["currency"] ?? "").toString(),
      status: (json["status"] ?? "").toString(),
      payoutId: json["payout_id"]?.toString(),
      createdAt: json["created_at"]?.toString(),
    );
  }
}

class DriverStats {
  const DriverStats({
    required this.totalAssigned,
    required this.totalDelivered,
    required this.totalCancelled,
    required this.completionRate,
    required this.earningsTotal,
    required this.earningsPending,
    required this.earningsPaid,
    required this.lastDeliveryAt,
  });

  final int totalAssigned;
  final int totalDelivered;
  final int totalCancelled;
  final num completionRate;
  final num earningsTotal;
  final num earningsPending;
  final num earningsPaid;
  final String? lastDeliveryAt;

  factory DriverStats.fromJson(Map<String, dynamic> json) {
    return DriverStats(
      totalAssigned: _toInt(json["total_assigned"]),
      totalDelivered: _toInt(json["total_delivered"]),
      totalCancelled: _toInt(json["total_cancelled"]),
      completionRate: _toNum(json["completion_rate"]),
      earningsTotal: _toNum(json["earnings_total"]),
      earningsPending: _toNum(json["earnings_pending"]),
      earningsPaid: _toNum(json["earnings_paid"]),
      lastDeliveryAt: json["last_delivery_at"]?.toString(),
    );
  }
}

num _toNum(dynamic value) {
  if (value is num) return value;
  return num.tryParse((value ?? "0").toString()) ?? 0;
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse((value ?? "0").toString()) ?? 0;
}
