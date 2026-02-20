class RoutePoint {
  const RoutePoint({required this.lat, required this.lng});

  final double lat;
  final double lng;

  factory RoutePoint.fromJson(Map<String, dynamic> json) {
    return RoutePoint(
      lat: (json["lat"] as num).toDouble(),
      lng: (json["lng"] as num).toDouble(),
    );
  }
}

class RouteInfo {
  const RouteInfo({
    required this.distanceKm,
    required this.etaMinutes,
    required this.polyline,
    this.note,
  });

  final num distanceKm;
  final int etaMinutes;
  final List<RoutePoint> polyline;
  final String? note;

  factory RouteInfo.fromJson(Map<String, dynamic> json) {
    return RouteInfo(
      distanceKm: json["distance_km"] is num
          ? json["distance_km"] as num
          : num.tryParse((json["distance_km"] ?? "0").toString()) ?? 0,
      etaMinutes: json["eta_minutes"] is int
          ? json["eta_minutes"] as int
          : int.tryParse((json["eta_minutes"] ?? "0").toString()) ?? 0,
      polyline: (json["polyline"] as List? ?? [])
          .map((item) => RoutePoint.fromJson(item as Map<String, dynamic>))
          .toList(),
      note: json["note"]?.toString(),
    );
  }
}
