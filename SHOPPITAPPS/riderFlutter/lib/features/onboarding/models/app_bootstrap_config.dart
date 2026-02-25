class AppBootstrapConfig {
  const AppBootstrapConfig({
    required this.forceUpdate,
    required this.minVersion,
    required this.latestVersion,
    required this.updateUrl,
    required this.message,
    required this.isBanned,
    required this.banReason,
  });

  final bool forceUpdate;
  final String? minVersion;
  final String? latestVersion;
  final String? updateUrl;
  final String? message;
  final bool isBanned;
  final String? banReason;

  factory AppBootstrapConfig.fromJson(Map<String, dynamic> json) {
    return AppBootstrapConfig(
      forceUpdate: json["force_update"] == true,
      minVersion: json["min_version"]?.toString(),
      latestVersion: json["latest_version"]?.toString(),
      updateUrl: json["update_url"]?.toString(),
      message: json["message"]?.toString(),
      isBanned: json["is_banned"] == true || json["banned"] == true,
      banReason: json["ban_reason"]?.toString(),
    );
  }
}
