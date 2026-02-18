import "dart:convert";

import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS (MUST BE FIRST)
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/auth_service.dart";
import "../models/auth_models.dart";
import "../../onboarding/presentation/splash_screen.dart";
import "otp_login_screen.dart";
import "register_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS (Adjusted to your Brand Green #2C9139)
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Your Brand Green
const Color kBackgroundColor = Color(0xFFFFFFFF);
const Color kInputFillColor = Color(0xFFF8F9FD);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _fcmController = TextEditingController();

  bool _submitting = false;
  String? _error;
  String? _detailedError;
  Map<String, String> _fieldErrors = {};

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _fcmController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _submitting = true;
      _error = null;
      _fieldErrors = {};
    });

    try {
      final dependencies = AppScope.of(context);
      final service = AuthService(
        apiClient: dependencies.apiClient,
        tokenStorage: dependencies.tokenStorage,
      );

      final result = await service.login(
        LoginRequest(
          email: _emailController.text.trim(),
          password: _passwordController.text,
          fcmDeviceToken: _fcmController.text.trim().isEmpty
              ? null
              : _fcmController.text.trim(),
        ),
      );

      if (!mounted) return;

      if (result.success) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const SplashScreen()),
          (route) => false,
        );
      } else {
        final msg = result.message.isNotEmpty
            ? result.message
            : "Login failed.";
        final code = result.statusCode != 0
            ? " (code: ${result.statusCode})"
            : "";
        setState(() {
          _error = "$msg$code";
          _fieldErrors = result.fieldErrors;
          _detailedError = result.fieldErrors.isNotEmpty
              ? jsonEncode(result.fieldErrors)
              : null;
        });
      }
    } on Exception catch (e) {
      if (!mounted) return;
      // Handle Dio and other network errors with more detail when available
      String details = e.toString();
      try {
        // DioError has a response field; we avoid importing dio directly here
        // but attempt to extract useful info if the exception exposes it.
        final resp = (e as dynamic).response;
        if (resp != null) {
          details += " | status=${resp.statusCode} | body=${resp.data}";
        }
      } catch (_) {}

      // Log to console for device debugging
      // ignore: avoid_print
      print("[LoginScreen] login error: $details");

      setState(() {
        _error = "Login failed: ${e.toString()}";
        _detailedError = details;
      });
    } finally {
      if (!mounted) return;
      setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          physics: const BouncingScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 40),

                // --- 1. Header Section ---
                Container(
                  height: 60,
                  width: 60,
                  decoration: BoxDecoration(
                    color: kPrimaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: const Icon(
                    Icons.delivery_dining,
                    color: kPrimaryColor,
                    size: 32,
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  "Welcome Back!",
                  style: TextStyle(
                    fontSize: 32,
                    fontWeight: FontWeight.w800,
                    color: kTextDark,
                    letterSpacing: -0.5,
                  ),
                ),
                const SizedBox(height: 8),
                const Text(
                  "Log in to continue your deliveries.",
                  style: TextStyle(
                    fontSize: 16,
                    color: kTextLight,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 40),

                // --- 2. Error Display ---
                if (_error != null)
                  Container(
                    width: double.infinity,
                    margin: const EdgeInsets.only(bottom: 20),
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.red.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.red.withOpacity(0.3)),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.error_outline,
                          color: Colors.red,
                          size: 20,
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Text(
                            _error!,
                            style: const TextStyle(
                              color: Colors.red,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),

                if (_detailedError != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 20),
                    child: ExpansionTile(
                      title: const Text(
                        "Error Details",
                        style: TextStyle(fontSize: 13, color: kTextLight),
                      ),
                      children: [
                        Container(
                          width: double.infinity,
                          color: Colors.grey.shade100,
                          padding: const EdgeInsets.all(12),
                          child: SelectableText(
                            _detailedError!,
                            style: const TextStyle(
                              fontFamily: 'monospace',
                              fontSize: 12,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                // --- 3. 3D Input Fields ---
                _buildModernInput(
                  controller: _emailController,
                  label: "Email Address",
                  icon: Icons.email_outlined,
                  inputType: TextInputType.emailAddress,
                  validator: (value) {
                    final serverError = _fieldErrors["email"];
                    if (serverError != null) return serverError;
                    if (value == null || value.trim().isEmpty)
                      return "Enter your email";
                    return null;
                  },
                ),
                const SizedBox(height: 20),
                _buildModernInput(
                  controller: _passwordController,
                  label: "Password",
                  icon: Icons.lock_outline,
                  isPassword: true,
                  validator: (value) {
                    final serverError = _fieldErrors["password"];
                    if (serverError != null) return serverError;
                    if (value == null || value.length < 6)
                      return "Password must be at least 6 chars";
                    return null;
                  },
                ),
                const SizedBox(height: 20),

                // Advanced Options
                Theme(
                  data: Theme.of(
                    context,
                  ).copyWith(dividerColor: Colors.transparent),
                  child: ExpansionTile(
                    title: const Text(
                      "Advanced Options",
                      style: TextStyle(fontSize: 14, color: kTextLight),
                    ),
                    tilePadding: EdgeInsets.zero,
                    children: [
                      _buildModernInput(
                        controller: _fcmController,
                        label: "FCM Token (Optional)",
                        icon: Icons.notifications_none,
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 40),

                // --- 4. Primary Action (Green Glow Button) ---
                SizedBox(
                  width: double.infinity,
                  height: 56,
                  child: ElevatedButton(
                    onPressed: _submitting ? null : _submit,
                    style:
                        ElevatedButton.styleFrom(
                          backgroundColor: kPrimaryColor,
                          foregroundColor: Colors.white,
                          elevation: 0,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          padding: EdgeInsets.zero,
                        ).copyWith(
                          shadowColor: MaterialStateProperty.all(
                            Colors.transparent,
                          ),
                        ),
                    child: Ink(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          colors: [
                            kPrimaryColor,
                            kPrimaryColor.withOpacity(0.8),
                          ],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: kPrimaryColor.withOpacity(0.4),
                            blurRadius: 20,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Container(
                        alignment: Alignment.center,
                        child: _submitting
                            ? const SizedBox(
                                height: 24,
                                width: 24,
                                child: CircularProgressIndicator(
                                  color: Colors.white,
                                  strokeWidth: 2,
                                ),
                              )
                            : const Text(
                                "Sign In",
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 24),

                // --- 5. Secondary Actions ---
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    TextButton(
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const OtpLoginScreen(),
                        ),
                      ),
                      child: const Text(
                        "Use OTP instead",
                        style: TextStyle(color: kTextLight),
                      ),
                    ),
                    TextButton(
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute(
                          builder: (_) => const RegisterScreen(),
                        ),
                      ),
                      child: Text(
                        "Join as Driver",
                        style: TextStyle(
                          color: kPrimaryColor,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildModernInput({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    bool isPassword = false,
    TextInputType? inputType,
    String? Function(String?)? validator,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.18),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: TextFormField(
        controller: controller,
        obscureText: isPassword,
        keyboardType: inputType,
        validator: validator,
        autovalidateMode: AutovalidateMode.onUserInteraction,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight),
          prefixIcon: Icon(icon, color: kTextLight),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 20,
            vertical: 20,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(
              color: kPrimaryColor.withOpacity(0.5),
              width: 1.5,
            ),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(
              color: Colors.red.withOpacity(0.5),
              width: 1,
            ),
          ),
          focusedErrorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Colors.red, width: 1.5),
          ),
        ),
      ),
    );
  }
}
