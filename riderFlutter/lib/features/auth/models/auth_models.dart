class LoginRequest {
  const LoginRequest({
    required this.email,
    required this.password,
    this.fcmDeviceToken,
  });

  final String email;
  final String password;
  final String? fcmDeviceToken;

  Map<String, dynamic> toJson() {
    return {
      "email": email,
      "password": password,
      if (fcmDeviceToken != null && fcmDeviceToken!.isNotEmpty)
        "fcm_device_token": fcmDeviceToken,
    };
  }
}

class RegisterRequest {
  const RegisterRequest({
    required this.name,
    required this.email,
    required this.phone,
    required this.password,
    required this.vehicleType,
    required this.licenseNumber,
    this.fcmDeviceToken,
  });

  final String name;
  final String email;
  final String phone;
  final String password;
  final String vehicleType;
  final String licenseNumber;
  final String? fcmDeviceToken;

  Map<String, dynamic> toJson() {
    return {
      "name": name,
      "email": email,
      "phone": phone,
      "password": password,
      "vehicle_type": vehicleType,
      "license_number": licenseNumber,
      if (fcmDeviceToken != null && fcmDeviceToken!.isNotEmpty)
        "fcm_device_token": fcmDeviceToken,
    };
  }
}

class AuthResult {
  const AuthResult({required this.token, this.driverId, this.role});

  final String token;
  final String? driverId;
  final String? role;

  factory AuthResult.fromJson(Map<String, dynamic> json) {
    return AuthResult(
      token: (json["token"] ?? "").toString(),
      driverId: json["driver_id"]?.toString(),
      role: json["role"]?.toString(),
    );
  }
}

class OtpSendRequest {
  const OtpSendRequest({this.email, this.phone});

  final String? email;
  final String? phone;

  Map<String, dynamic> toJson() {
    return {
      if (email != null && email!.isNotEmpty) "email": email,
      if (phone != null && phone!.isNotEmpty) "phone": phone,
    };
  }
}

class OtpVerifyRequest {
  const OtpVerifyRequest({this.email, this.phone, required this.code});

  final String? email;
  final String? phone;
  final String code;

  Map<String, dynamic> toJson() {
    return {
      "verification_code": code,
      if (email != null && email!.isNotEmpty) "email": email,
      if (phone != null && phone!.isNotEmpty) "phone": phone,
    };
  }
}

class RegisterOtpVerifyRequest {
  const RegisterOtpVerifyRequest({required this.email, required this.code});

  final String email;
  final String code;

  Map<String, dynamic> toJson() {
    return {
      "email": email,
      "verification_code": code,
    };
  }
}

class RegisterOtpResendRequest {
  const RegisterOtpResendRequest({required this.email});

  final String email;

  Map<String, dynamic> toJson() {
    return {"email": email};
  }
}

class OtpLoginRequest {
  const OtpLoginRequest({this.email, this.phone, required this.code});

  final String? email;
  final String? phone;
  final String code;

  Map<String, dynamic> toJson() {
    return {
      if (email != null && email!.isNotEmpty) "email": email,
      if (phone != null && phone!.isNotEmpty) "phone": phone,
      "verification_code": code,
    };
  }
}
