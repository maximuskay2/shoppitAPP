import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class BiometricService {
  static final BiometricService _instance = BiometricService._internal();
  factory BiometricService() => _instance;
  BiometricService._internal();

  final LocalAuthentication _localAuth = LocalAuthentication();
  final FlutterSecureStorage _secureStorage = const FlutterSecureStorage();

  static const String _keyBiometricEnabled = 'biometric_enabled';
  static const String _keyStoredToken = 'biometric_token';

  /// Check if device supports biometric authentication
  Future<bool> isBiometricAvailable() async {
    try {
      final canCheckBiometrics = await _localAuth.canCheckBiometrics;
      final isDeviceSupported = await _localAuth.isDeviceSupported();
      return canCheckBiometrics && isDeviceSupported;
    } on PlatformException {
      return false;
    }
  }

  /// Get available biometric types
  Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _localAuth.getAvailableBiometrics();
    } on PlatformException {
      return [];
    }
  }

  /// Check if biometric login is enabled by user
  Future<bool> isBiometricEnabled() async {
    final enabled = await _secureStorage.read(key: _keyBiometricEnabled);
    return enabled == 'true' && await isBiometricAvailable();
  }

  /// Enable biometric authentication and store token
  Future<bool> enableBiometric(String token) async {
    try {
      // First authenticate to confirm user identity
      final authenticated = await authenticate(
        reason: 'Authenticate to enable biometric login',
      );

      if (authenticated) {
        await _secureStorage.write(key: _keyBiometricEnabled, value: 'true');
        await _secureStorage.write(key: _keyStoredToken, value: token);
        return true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  /// Disable biometric authentication
  Future<void> disableBiometric() async {
    await _secureStorage.delete(key: _keyBiometricEnabled);
    await _secureStorage.delete(key: _keyStoredToken);
  }

  /// Authenticate using biometrics
  Future<bool> authenticate({String reason = 'Authenticate to continue'}) async {
    try {
      return await _localAuth.authenticate(
        localizedReason: reason,
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );
    } on PlatformException catch (e) {
      print('Biometric authentication error: ${e.message}');
      return false;
    }
  }

  /// Authenticate and return stored token
  Future<String?> authenticateAndGetToken() async {
    try {
      final isEnabled = await isBiometricEnabled();
      if (!isEnabled) return null;

      final authenticated = await authenticate(
        reason: 'Use biometrics to login',
      );

      if (authenticated) {
        return await _secureStorage.read(key: _keyStoredToken);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  /// Update stored token (e.g., after token refresh)
  Future<void> updateStoredToken(String token) async {
    final isEnabled = await isBiometricEnabled();
    if (isEnabled) {
      await _secureStorage.write(key: _keyStoredToken, value: token);
    }
  }

  /// Get biometric type display name
  String getBiometricTypeName(List<BiometricType> types) {
    if (types.contains(BiometricType.face)) {
      return 'Face ID';
    } else if (types.contains(BiometricType.fingerprint)) {
      return 'Fingerprint';
    } else if (types.contains(BiometricType.iris)) {
      return 'Iris';
    }
    return 'Biometric';
  }

  /// Get detailed status message
  Future<String> getStatusMessage() async {
    try {
      final canCheck = await _localAuth.canCheckBiometrics;
      final isSupported = await _localAuth.isDeviceSupported();

      if (!isSupported) {
        return 'Biometric authentication is not supported on this device';
      }

      if (!canCheck) {
        return 'No biometric credentials enrolled. Please set up fingerprint or face recognition in device settings.';
      }

      final biometrics = await getAvailableBiometrics();
      if (biometrics.isEmpty) {
        return 'No biometrics available';
      }

      return 'Biometric authentication available: ${getBiometricTypeName(biometrics)}';
    } catch (e) {
      return 'Unable to check biometric status';
    }
  }
}
