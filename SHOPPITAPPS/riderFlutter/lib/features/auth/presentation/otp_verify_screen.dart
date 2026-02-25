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

class OtpVerifyScreen extends StatefulWidget {
  const OtpVerifyScreen({
    super.key,
    this.email,
    this.phone,
    this.useRegisterEndpoint = true,
  });

  final String? email;
  final String? phone;
  final bool useRegisterEndpoint;

  @override
  State<OtpVerifyScreen> createState() => _OtpVerifyScreenState();
}

class _OtpVerifyScreenState extends State<OtpVerifyScreen> {
  final _codeController = TextEditingController();
  bool _submitting = false;
  bool _resending = false;
  String? _error;
  int _resendSeconds = 0;
  Timer? _timer;

  @override
  void dispose() {
    _timer?.cancel();
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

  Future<void> _verify() async {
    final code = _codeController.text.trim();
    if (widget.useRegisterEndpoint && (widget.email == null || widget.email!.isEmpty)) {
      setState(() => _error = "Email is required for verification.");
      return;
    }
    if (!widget.useRegisterEndpoint &&
        (widget.email == null || widget.email!.isEmpty) &&
        (widget.phone == null || widget.phone!.isEmpty)) {
      setState(() => _error = "Email or phone is required for verification.");
      return;
    }
    if (code.isEmpty) {
      setState(() => _error = "Enter the verification code.");
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final dependencies = AppScope.of(context);
      final service = AuthService(
        apiClient: dependencies.apiClient,
        tokenStorage: dependencies.tokenStorage,
      );

      final result = widget.useRegisterEndpoint
          ? await service.verifyRegisterOtp(
        RegisterOtpVerifyRequest(email: widget.email ?? "", code: code),
      )
          : await service.verifyOtp(
        OtpVerifyRequest(
          email: widget.email,
          phone: widget.phone,
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
          _error = result.message.isEmpty ? "Verification failed." : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Verification failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _submitting = false);
    }
  }

  Future<void> _resend() async {
    if (_resending || _resendSeconds > 0) return;
    if (widget.useRegisterEndpoint && (widget.email == null || widget.email!.isEmpty)) {
      setState(() => _error = "Email is required for resend.");
      return;
    }
    if (!widget.useRegisterEndpoint &&
        (widget.email == null || widget.email!.isEmpty) &&
        (widget.phone == null || widget.phone!.isEmpty)) {
      setState(() => _error = "Email or phone is required for resend.");
      return;
    }

    setState(() {
      _resending = true;
      _error = null;
    });

    try {
      final dependencies = AppScope.of(context);
      final service = AuthService(
        apiClient: dependencies.apiClient,
        tokenStorage: dependencies.tokenStorage,
      );

      final result = widget.useRegisterEndpoint
          ? await service.resendRegisterOtp(
        RegisterOtpResendRequest(email: widget.email ?? ""),
      )
          : await service.sendOtp(
        OtpSendRequest(email: widget.email, phone: widget.phone),
      );

      if (!mounted) return;

      if (result.success) {
        _startResendCountdown(60);
      } else {
        setState(() {
          _error = result.message.isEmpty ? "Resend failed." : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Resend failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _resending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Determine target (Email or Phone) for display
    final String target = (widget.phone != null && widget.phone!.isNotEmpty)
        ? widget.phone!
        : (widget.email ?? "your email");

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

              const SizedBox(height: 40),

              // --- 2. Header & Instructions ---
              Container(
                height: 60,
                width: 60,
                decoration: BoxDecoration(
                  color: kPrimaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.mark_email_read, color: kPrimaryColor, size: 30),
              ),
              const SizedBox(height: 24),
              const Text(
                "Verify Account",
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: kTextDark,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 12),
              RichText(
                text: TextSpan(
                  style: const TextStyle(
                    fontSize: 16,
                    color: kTextLight,
                    fontWeight: FontWeight.w500,
                    height: 1.5,
                  ),
                  children: [
                    const TextSpan(text: "We have sent a verification code to \n"),
                    TextSpan(
                      text: target,
                      style: const TextStyle(
                        color: kTextDark,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 40),

              // --- 3. Error Display ---
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

              // --- 4. Input Field (3D Style) ---
              _buildModernInput(
                controller: _codeController,
                label: "Enter Verification Code",
                icon: Icons.lock_open,
                inputType: TextInputType.number,
              ),

              const SizedBox(height: 40),

              // --- 5. Verify Button (Primary) ---
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: _submitting ? null : _verify,
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
                      child: _submitting
                          ? const SizedBox(
                        height: 24,
                        width: 24,
                        child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                      )
                          : const Text(
                        "Verify Now",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 24),

              // --- 6. Resend Option ---
              Center(
                child: TextButton(
                  onPressed: _resendSeconds > 0 || _resending ? null : _resend,
                  style: TextButton.styleFrom(
                    foregroundColor: kTextLight,
                  ),
                  child: _resending
                      ? const SizedBox(
                    height: 16,
                    width: 16,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                      : RichText(
                    textAlign: TextAlign.center,
                    text: TextSpan(
                      style: const TextStyle(fontSize: 14, color: kTextLight),
                      children: [
                        const TextSpan(text: "Didn't receive the code? "),
                        TextSpan(
                          text: _resendSeconds > 0
                              ? "Wait ${_resendSeconds}s"
                              : "Resend",
                          style: TextStyle(
                            color: _resendSeconds > 0 ? kTextLight : kPrimaryColor,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),

              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  // --- Helper Widget for 3D/Floating Inputs ---
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
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark, fontSize: 18, letterSpacing: 2),
        textAlign: TextAlign.center, // Center text for OTP
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight, fontSize: 14, letterSpacing: 0),
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
}
