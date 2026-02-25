class DriverVehicle {
  const DriverVehicle({
    required this.id,
    required this.vehicleType,
    this.licenseNumber,
    this.plateNumber,
    this.color,
    this.model,
    required this.isActive,
  });

  final String id;
  final String vehicleType;
  final String? licenseNumber;
  final String? plateNumber;
  final String? color;
  final String? model;
  final bool isActive;

  factory DriverVehicle.fromJson(Map<String, dynamic> json) {
    return DriverVehicle(
      id: json["id"].toString(),
      vehicleType: (json["vehicle_type"] ?? "").toString(),
      licenseNumber: json["license_number"]?.toString(),
      plateNumber: json["plate_number"]?.toString(),
      color: json["color"]?.toString(),
      model: json["model"]?.toString(),
      isActive: json["is_active"] == true,
    );
  }
}
