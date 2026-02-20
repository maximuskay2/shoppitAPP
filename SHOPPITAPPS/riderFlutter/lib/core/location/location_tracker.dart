import "dart:async";

import "package:flutter/foundation.dart";
import "package:geolocator/geolocator.dart";

import "../network/api_client.dart";
import "../network/api_paths.dart";

class LocationTracker {
  LocationTracker({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;
  StreamSubscription<Position>? _subscription;
  DateTime? _lastSent;

  Future<bool> ensurePermission() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return false;
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    return permission == LocationPermission.always ||
        permission == LocationPermission.whileInUse;
  }

  Future<void> startTracking({bool includeInitial = true}) async {
    if (_subscription != null) return;

    final allowed = await ensurePermission();
    if (!allowed) return;

    if (includeInitial) {
      try {
        final position = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high,
        );
        await _sendLocation(position, initial: true);
      } catch (error) {
        debugPrint("Failed to get initial location: $error");
      }
    }

    const settings = LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 10,
    );

    _subscription = Geolocator.getPositionStream(locationSettings: settings)
        .listen((position) async {
      final now = DateTime.now();
      if (_lastSent != null && now.difference(_lastSent!).inSeconds < 5) {
        return;
      }
      _lastSent = now;
      await _sendLocation(position, initial: false);
    }, onError: (error) {
      debugPrint("Location stream error: $error");
    });
  }

  Future<void> stopTracking() async {
    await _subscription?.cancel();
    _subscription = null;
  }

  Future<void> _sendLocation(Position position, {required bool initial}) async {
    final endpoint = initial ? ApiPaths.driverLocation : ApiPaths.driverLocationUpdate;
    try {
      await _apiClient.dio.post(
        endpoint,
        data: {
          "latitude": position.latitude,
          "longitude": position.longitude,
          "bearing": position.heading,
          "speed": position.speed,
        },
      );
    } catch (error) {
      debugPrint("Failed to send location: $error");
    }
  }
}
