import "dart:async";
import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/auth_service.dart";
import "../models/auth_models.dart";
import "../../onboarding/presentation/splash_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFFFFFFF);
const Color kInputFillColor = Color(0xFFF8F9FD);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class OtpLoginScreen extends StatefulWidget {
  const OtpLoginScreen({super.key});

  @override
  State<OtpLoginScreen> createState() => _OtpLoginScreenState();
}

class _OtpLoginScreenState extends State<OtpLoginScreen> {
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _codeController = TextEditingController();

  bool _sending = false;
  bool _verifying = false;
  String? _error;
  int _resendSeconds = 0;
  Timer? _timer;

  // false = Email, true = Phone
  bool _usePhone = false;

  @override
  void dispose() {
    _timer?.cancel();
    _emailController.dispose();
    _phoneController.dispose();
    _codeController.dispose();
    super.dispose();
  }

  void _startResendCountdown(int seconds) {
    _timer?.cancel();
    setState(() => _resendSeconds = seconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendSeconds <= 1) {
        timer.cancel();
        setState(() => _resendSeconds = 0);
      } else {
        setState(() => _resendSeconds -= 1);
      }
    });
  }

  Future<void> _sendCode() async {
    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    if (_usePhone && phone.isEmpty) {
      setState(() => _error = "Enter your phone number.");
      return;
    }
    if (!_usePhone && email.isEmpty) {
      setState(() => _error = "Enter your email.");
      return;
    }

    setState(() {
      _sending = true;
      _error = null;
    });

    try {
      final dependencies = AppScope.of(context);
      final service = AuthService(
        apiClient: dependencies.apiClient,
        tokenStorage: dependencies.tokenStorage,
      );

      final result = await service.sendOtp(
        OtpSendRequest(
          email: _usePhone ? null : email,
          phone: _usePhone ? phone : null,
        ),
      );
      if (!mounted) return;

      if (result.success) {
        _startResendCountdown(60);
      } else {
        setState(() {
          _error = result.message.isEmpty ? "Failed to send code." : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to send code. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _sending = false);
    }
  }

  Future<void> _verify() async {
    final email = _emailController.text.trim();
    final phone = _phoneController.text.trim();
    final code = _codeController.text.trim();
    if (_usePhone && phone.isEmpty) {
      setState(() => _error = "Enter phone and code.");
      return;
    }
    if (!_usePhone && email.isEmpty) {
      setState(() => _error = "Enter email and code.");
      return;
    }
    if (code.isEmpty) {
      setState(() => _error = "Enter email and code.");
      return;
    }

    setState(() {
      _verifying = true;
      _error = null;
    });

    try {
      final dependencies = AppScope.of(context);
      final service = AuthService(
        apiClient: dependencies.apiClient,
        tokenStorage: dependencies.tokenStorage,
      );

      final result = await service.loginWithOtp(
        OtpLoginRequest(
          email: _usePhone ? null : email,
          phone: _usePhone ? phone : null,
          code: code,
        ),
      );

      if (!mounted) return;

      if (result.success) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          Navigator.of(context, rootNavigator: true).pushAndRemoveUntil(
            MaterialPageRoute(builder: (_) => const SplashScreen()),
            (route) => false,
          );
        });
      } else {
        setState(() {
          _error = result.message.isEmpty ? "Login failed." : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Login failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _verifying = false);
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
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 20),

              // --- 1. Custom Back Button ---
              GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.05),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: const Icon(Icons.arrow_back, color: kTextDark),
                ),
              ),

              const SizedBox(height: 30),

              // --- 2. Header ---
              const Text(
                "Passwordless Login",
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: kTextDark,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                _usePhone
                    ? "We'll send a code to your mobile."
                    : "We'll send a code to your email.",
                style: const TextStyle(
                  fontSize: 16,
                  color: kTextLight,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 30),

              // --- 3. Custom 3D Toggle (Email vs Phone) ---
              Row(
                children: [
                  Expanded(
                    child: _buildToggleOption(
                      label: "Email",
                      icon: Icons.email_outlined,
                      isSelected: !_usePhone,
                      onTap: () => setState(() {
                        _usePhone = false;
                        _error = null;
                      }),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: _buildToggleOption(
                      label: "Phone",
                      icon: Icons.phone_android,
                      isSelected: _usePhone,
                      onTap: () => setState(() {
                        _usePhone = true;
                        _error = null;
                      }),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 30),

              // --- 4. Error Display ---
              if (_error != null)
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 20),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.red.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline, color: Colors.red, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          _error!,
                          style: const TextStyle(color: Colors.red, fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),

              // --- 5. Main Input Fields ---
              if (_usePhone)
                _buildModernInput(
                  controller: _phoneController,
                  label: "Phone Number",
                  icon: Icons.phone,
                  inputType: TextInputType.phone,
                )
              else
                _buildModernInput(
                  controller: _emailController,
                  label: "Email Address",
                  icon: Icons.alternate_email,
                  inputType: TextInputType.emailAddress,
                ),

              const SizedBox(height: 16),

              // --- 6. Send Code Button (Secondary Action) ---
              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: _sending || _resendSeconds > 0 ? null : _sendCode,
                  style: TextButton.styleFrom(
                    foregroundColor: kPrimaryColor,
                  ),
                  child: _sending
                      ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2)
                  )
                      : Text(
                    _resendSeconds > 0
                        ? "Resend code in ${_resendSeconds}s"
                        : "Get Verification Code",
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      color: _resendSeconds > 0 ? kTextLight : kPrimaryColor,
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 16),

              _buildModernInput(
                controller: _codeController,
                label: "Enter 6-digit Code",
                icon: Icons.lock_clock_outlined,
                inputType: TextInputType.number,
              ),

              const SizedBox(height: 40),

              // --- 7. Primary Action (Verify) ---
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: _verifying ? null : _verify,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kPrimaryColor,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                    padding: EdgeInsets.zero,
                  ).copyWith(
                    shadowColor: MaterialStateProperty.all(Colors.transparent),
                  ),
                  child: Ink(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [kPrimaryColor, kPrimaryColor.withOpacity(0.8)],
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
                      child: _verifying
                          ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                          : const Text(
                        "Sign In",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // --- Helper: Modern Input ---
  Widget _buildModernInput({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    TextInputType? inputType,
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
      child: TextField(
        controller: controller,
        keyboardType: inputType,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight),
          prefixIcon: Icon(icon, color: kTextLight),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 20),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: BorderSide(color: kPrimaryColor.withOpacity(0.5), width: 1.5),
          ),
        ),
      ),
    );
  }

  // --- Helper: 3D Toggle Option ---
  Widget _buildToggleOption({
    required String label,
    required IconData icon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        height: 80,
        decoration: BoxDecoration(
          color: isSelected ? Colors.white : const Color(0xFFF5F7FA),
          borderRadius: BorderRadius.circular(16),
          border: isSelected
              ? Border.all(color: kPrimaryColor, width: 2)
              : Border.all(color: Colors.transparent),
          boxShadow: isSelected
              ? [
            BoxShadow(
              color: kPrimaryColor.withOpacity(0.2),
              blurRadius: 15,
              offset: const Offset(0, 8),
            )
          ]
              : [],
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: isSelected ? kPrimaryColor : kTextLight,
              size: 28,
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontWeight: FontWeight.bold,
                color: isSelected ? kPrimaryColor : kTextLight,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
