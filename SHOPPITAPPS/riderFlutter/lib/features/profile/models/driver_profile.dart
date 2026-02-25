class UserProfile {
  const UserProfile({
    required this.id,
    required this.name,
    required this.email,
    required this.phone,
    this.avatar,
  });

  final String id;
  final String name;
  final String email;
  final String phone;
  final String? avatar;

  factory UserProfile.fromJson(Map<String, dynamic> json) {
    return UserProfile(
      id: json["id"].toString(),
      name: (json["name"] ?? "").toString(),
      email: (json["email"] ?? "").toString(),
      phone: (json["phone"] ?? "").toString(),
      avatar: json["avatar"]?.toString(),
    );
  }
}

class DriverProfile {
  const DriverProfile({
    required this.user,
    required this.vehicleType,
    required this.licenseNumber,
    required this.isVerified,
    required this.isOnline,
  });

  final UserProfile user;
  final String vehicleType;
  final String licenseNumber;
  final bool isVerified;
  final bool isOnline;

  factory DriverProfile.fromJson(Map<String, dynamic> json) {
    final driver = json["driver"] as Map<String, dynamic>? ?? {};
    return DriverProfile(
      user: UserProfile.fromJson(json["user"] as Map<String, dynamic>),
      vehicleType: (driver["vehicle_type"] ?? "").toString(),
      licenseNumber: (driver["license_number"] ?? "").toString(),
      isVerified: _parseBool(driver["is_verified"]),
      isOnline: _parseBool(driver["is_online"]),
    );
  }
}

bool _parseBool(dynamic value) {
  if (value is bool) return value;
  if (value is num) return value == 1;
  if (value is String) {
    final normalized = value.toLowerCase();
    return normalized == "1" || normalized == "true" || normalized == "yes";
  }
  return false;
}

class DriverProfileUpdateRequest {
  const DriverProfileUpdateRequest({
    required this.name,
    required this.phone,
    required this.vehicleType,
    required this.licenseNumber,
  });

  final String name;
  final String phone;
  final String vehicleType;
  final String licenseNumber;

  Map<String, dynamic> toJson() {
    return {
      "name": name,
      "phone": phone,
      "vehicle_type": vehicleType,
      "license_number": licenseNumber,
    };
  }
}
