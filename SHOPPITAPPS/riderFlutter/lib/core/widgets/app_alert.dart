import "package:flutter/material.dart";

enum AppAlertType { info, success, warning, error }

class AppAlert {
  static void show(
    BuildContext context, {
    required String message,
    AppAlertType type = AppAlertType.info,
  }) {
    final snackBar = SnackBar(
      content: Text(message),
      backgroundColor: _backgroundColor(context, type),
      behavior: SnackBarBehavior.floating,
    );

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(snackBar);
  }

  static Color _backgroundColor(BuildContext context, AppAlertType type) {
    final scheme = Theme.of(context).colorScheme;
    switch (type) {
      case AppAlertType.success:
        return Colors.green.shade700;
      case AppAlertType.warning:
        return Colors.orange.shade700;
      case AppAlertType.error:
        return scheme.error;
      case AppAlertType.info:
      default:
        return scheme.primary;
    }
  }
}
