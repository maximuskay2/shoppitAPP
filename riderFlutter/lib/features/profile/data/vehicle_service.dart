import "../../../core/network/api_client.dart";
import "../../../core/network/api_paths.dart";
import "../../../core/network/api_response.dart";
import "../models/driver_vehicle.dart";

class VehicleService {
  VehicleService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ApiResponse<List<DriverVehicle>>> fetchVehicles() async {
    final response = await _apiClient.dio.get(ApiPaths.driverVehicles);
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) {
        if (data is Map<String, dynamic> && data["vehicles"] is List) {
          return (data["vehicles"] as List)
              .map((item) => DriverVehicle.fromJson(item as Map<String, dynamic>))
              .toList();
        }
        return <DriverVehicle>[];
      },
    );
  }

  Future<ApiResponse<DriverVehicle>> createVehicle({
    required String vehicleType,
    String? licenseNumber,
    String? plateNumber,
    String? color,
    String? model,
    bool isActive = false,
  }) async {
    final response = await _apiClient.dio.post(
      ApiPaths.driverVehicles,
      data: {
        "vehicle_type": vehicleType,
        "license_number": licenseNumber,
        "plate_number": plateNumber,
        "color": color,
        "model": model,
        "is_active": isActive,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverVehicle.fromJson(data["vehicle"] as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<DriverVehicle>> updateVehicle({
    required String id,
    String? vehicleType,
    String? licenseNumber,
    String? plateNumber,
    String? color,
    String? model,
    bool? isActive,
  }) async {
    final response = await _apiClient.dio.put(
      "${ApiPaths.driverVehicles}/$id",
      data: {
        if (vehicleType != null) "vehicle_type": vehicleType,
        if (licenseNumber != null) "license_number": licenseNumber,
        if (plateNumber != null) "plate_number": plateNumber,
        if (color != null) "color": color,
        if (model != null) "model": model,
        if (isActive != null) "is_active": isActive,
      },
    );

    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (data) => data == null
          ? null
          : DriverVehicle.fromJson(data["vehicle"] as Map<String, dynamic>),
    );
  }

  Future<ApiResponse<void>> deleteVehicle(String id) async {
    final response = await _apiClient.dio.delete(
      "${ApiPaths.driverVehicles}/$id",
    );
    return ApiResponse.fromJson(
      response.data as Map<String, dynamic>,
      (_) => null,
    );
  }
}
