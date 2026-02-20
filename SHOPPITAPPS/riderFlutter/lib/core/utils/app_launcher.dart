import "package:url_launcher/url_launcher.dart";

class AppLauncher {
  const AppLauncher._();

  static Future<bool> openMaps({
    required double latitude,
    required double longitude,
    String? label,
  }) async {
    final query = "${latitude.toString()},${longitude.toString()}";
    final encodedLabel = label == null || label.isEmpty
        ? ""
        : " (${Uri.encodeComponent(label)})";
    final uri = Uri.parse("https://www.google.com/maps/search/?api=1&query=$query$encodedLabel");
    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  static Future<bool> callPhone(String phone) async {
    final normalized = phone.trim();
    if (normalized.isEmpty) return false;
    final uri = Uri(scheme: "tel", path: normalized);
    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  static Future<bool> openUrl(String url) async {
    final trimmed = url.trim();
    if (trimmed.isEmpty) return false;
    final uri = Uri.tryParse(trimmed);
    if (uri == null) return false;
    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  static Future<bool> openWhatsApp(String phone) async {
    final normalized = phone.replaceAll(RegExp(r"[^0-9+]"), "");
    if (normalized.isEmpty) return false;
    final uri = Uri.parse("https://wa.me/$normalized");
    return launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}
