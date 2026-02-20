import "package:shared_preferences/shared_preferences.dart";

class DriverSettings {
  const DriverSettings({
    required this.notificationsEnabled,
    required this.navigationPreference,
    required this.languageCode,
  });

  final bool notificationsEnabled;
  final String navigationPreference;
  final String languageCode;

  DriverSettings copyWith({
    bool? notificationsEnabled,
    String? navigationPreference,
    String? languageCode,
  }) {
    return DriverSettings(
      notificationsEnabled:
          notificationsEnabled ?? this.notificationsEnabled,
      navigationPreference:
          navigationPreference ?? this.navigationPreference,
      languageCode: languageCode ?? this.languageCode,
    );
  }

  static const defaults = DriverSettings(
    notificationsEnabled: true,
    navigationPreference: "google_maps",
    languageCode: "en",
  );
}

class SettingsStorage {
  static const _keyNotifications = "driver.settings.notifications";
  static const _keyNavigation = "driver.settings.navigation";
  static const _keyLanguage = "driver.settings.language";

  Future<DriverSettings> load() async {
    final prefs = await SharedPreferences.getInstance();
    return DriverSettings(
      notificationsEnabled:
          prefs.getBool(_keyNotifications) ?? DriverSettings.defaults.notificationsEnabled,
      navigationPreference:
          prefs.getString(_keyNavigation) ?? DriverSettings.defaults.navigationPreference,
      languageCode:
          prefs.getString(_keyLanguage) ?? DriverSettings.defaults.languageCode,
    );
  }

  Future<void> save(DriverSettings settings) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_keyNotifications, settings.notificationsEnabled);
    await prefs.setString(_keyNavigation, settings.navigationPreference);
    await prefs.setString(_keyLanguage, settings.languageCode);
  }
}
