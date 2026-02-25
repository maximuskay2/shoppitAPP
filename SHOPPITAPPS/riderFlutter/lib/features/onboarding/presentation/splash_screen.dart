import "package:flutter/material.dart";
import "package:package_info_plus/package_info_plus.dart";

import "../../../app/app_scope.dart";
import "../../../core/utils/app_launcher.dart";
import "../../auth/presentation/login_screen.dart";
import "../../auth/presentation/resend_verification_screen.dart";
import "../../home/presentation/home_shell.dart";
import "../../profile/data/profile_service.dart";
import "../data/app_bootstrap_service.dart";
import "verification_status_screen.dart";

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  bool _loading = true;
  String? _error;
  bool _navigated = false;
  bool _blocked = false;
  bool _emailNotVerified = false;
  String? _blockTitle;
  String? _blockMessage;
  String? _blockActionLabel;
  String? _blockActionUrl;

  @override
  void initState() {
    super.initState();
    // Do not call _bootstrap here, as context is not ready for inherited widgets
  }

  bool _bootstrapRun = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Only call _bootstrap once after first build
    if (!_bootstrapRun) {
      _bootstrapRun = true;
      _bootstrap();
    }
  }

  Future<void> _bootstrap() async {
    debugPrint('[SplashScreen] Starting bootstrap');
    setState(() {
      _loading = true;
      _error = null;
      _blocked = false;
      _emailNotVerified = false;
    });

    final token = await AppScope.of(context).tokenStorage.readToken();
    debugPrint('[SplashScreen] Token read: $token');
    if (!mounted || _navigated) {
      debugPrint('[SplashScreen] Not mounted or already navigated, aborting');
      return;
    }

    if (token == null || token.isEmpty) {
      debugPrint('[SplashScreen] No token found, navigating to LoginScreen');
      _navigate(const LoginScreen());
      return;
    }

    final bootstrapOk = await _runBootstrapChecks();
    debugPrint('[SplashScreen] Bootstrap checks result: $bootstrapOk');
    if (!mounted || _navigated) {
      debugPrint('[SplashScreen] Not mounted or already navigated after bootstrap checks, aborting');
      return;
    }
    if (!bootstrapOk) {
      debugPrint('[SplashScreen] Bootstrap checks failed, loading stopped');
      setState(() => _loading = false);
      return;
    }

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);

    try {
      final result = await service.fetchProfile();
      debugPrint('[SplashScreen] Profile fetch result: ${result.success}, data: ${result.data}');
      if (!mounted || _navigated) {
        debugPrint('[SplashScreen] Not mounted or already navigated after profile fetch, aborting');
        return;
      }

      final profile = result.data;
      if (result.success && profile != null) {
        debugPrint('[SplashScreen] Profile success, isVerified: ${profile.isVerified}');
        if (profile.isVerified) {
          debugPrint('[SplashScreen] Navigating to HomeShell');
          _navigate(const HomeShell());
        } else {
          debugPrint('[SplashScreen] Navigating to VerificationStatusScreen');
          _navigate(
            VerificationStatusScreen(
              isVerified: false,
              statusLabel: "Pending verification",
            ),
          );
        }
        return;
      }

      debugPrint('[SplashScreen] Profile fetch failed, error: ${result.message}');
        final isEmailNotVerified = (result.message.toLowerCase().contains("email") &&
            result.message.toLowerCase().contains("verif"));
        setState(() {
          _error = result.message.isEmpty ? "Unable to load profile." : result.message;
          _loading = false;
          _emailNotVerified = isEmailNotVerified;
        });
    } catch (e, stack) {
      debugPrint('[SplashScreen] Exception during profile fetch: $e\n$stack');
      if (!mounted || _navigated) {
        debugPrint('[SplashScreen] Not mounted or already navigated after exception, aborting');
        return;
      }
      // Check for 403 email not verified (Dio may throw or return error response)
      final errStr = e.toString().toLowerCase();
      final isEmailNotVerified = errStr.contains("email") && errStr.contains("verif");
      setState(() {
        _error = "Unable to load driver status.";
        _loading = false;
        _emailNotVerified = isEmailNotVerified;
      });
    }
  }

  Future<bool> _runBootstrapChecks() async {
    try {
      final appInfo = await PackageInfo.fromPlatform();
      final service = AppBootstrapService(
        apiClient: AppScope.of(context).apiClient,
      );
      final result = await service.fetchConfig();
      if (!mounted) return false;

      if (result.success && result.data != null) {
        final config = result.data!;
        if (config.isBanned) {
          _setBlocked(
            title: "Account restricted",
            message: config.banReason ??
                "Your account is restricted. Contact support for help.",
            actionLabel: "Back to login",
          );
          await AppScope.of(context).tokenStorage.clearToken();
          return false;
        }

        // Removed update enforcement: app will run regardless of version
      }
    } catch (_) {
      // Ignore bootstrap failures and continue.
    }

    return true;
  }

  void _setBlocked({
    required String title,
    required String message,
    String? actionLabel,
    String? actionUrl,
  }) {
    _blocked = true;
    _blockTitle = title;
    _blockMessage = message;
    _blockActionLabel = actionLabel;
    _blockActionUrl = actionUrl;
  }

  void _navigate(Widget screen) {
    if (_navigated) return;
    _navigated = true;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => screen),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: _loading
              ? const CircularProgressIndicator()
              : _blocked
                ? _buildBlockedContent(context)
                : Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.local_shipping, size: 48),
                    const SizedBox(height: 12),
                    Text(
                      _error ?? "Ready",
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: _emailNotVerified
                          ? () => _navigate(const ResendVerificationScreen())
                          : _bootstrap,
                      child: Text(
                        _emailNotVerified ? "Verify email / phone" : "Retry",
                      ),
                    ),
                  ],
                ),
        ),
      ),
    );
  }

  Widget _buildBlockedContent(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(Icons.warning_amber, size: 48),
        const SizedBox(height: 12),
        Text(
          _blockTitle ?? "Access blocked",
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          _blockMessage ?? "Please contact support.",
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 16),
        if (_blockActionLabel != null)
          SizedBox(
            width: 220,
            child: FilledButton(
              onPressed: () async {
                final url = _blockActionUrl;
                if (url != null && url.isNotEmpty) {
                  await AppLauncher.openUrl(url);
                  return;
                }
                if (!mounted) return;
                _navigate(const LoginScreen());
              },
              child: Text(_blockActionLabel ?? "Continue"),
            ),
          ),
      ],
    );
  }
}
