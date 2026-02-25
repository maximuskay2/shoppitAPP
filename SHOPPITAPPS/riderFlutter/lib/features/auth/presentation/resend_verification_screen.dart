import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/auth_service.dart";
import "../models/auth_models.dart";
import "otp_verify_screen.dart";

const Color _kPrimary = Color(0xFF2C9139);
const Color _kTextDark = Color(0xFF1A1D26);
const Color _kTextLight = Color(0xFF9EA3AE);

/// Shown when driver's email is not verified. Lets them enter email or phone
/// to receive OTP and complete verification.
class ResendVerificationScreen extends StatefulWidget {
  const ResendVerificationScreen({super.key});

  @override
  State<ResendVerificationScreen> createState() =>
      _ResendVerificationScreenState();
}

class _ResendVerificationScreenState extends State<ResendVerificationScreen> {
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  bool _usePhone = false;
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _emailController.dispose();
    _phoneController.dispose();
    super.dispose();
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

      if (_usePhone) {
        final result = await service.sendOtp(
          OtpSendRequest(email: null, phone: phone),
        );
        if (!mounted) return;
        if (result.success) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder: (_) => OtpVerifyScreen(
                phone: phone,
                useRegisterEndpoint: false,
              ),
            ),
          );
        } else {
          setState(() {
            _error = result.message.isEmpty ? "Failed to send code." : result.message;
            _sending = false;
          });
        }
      } else {
        final result = await service.resendRegisterOtp(
          RegisterOtpResendRequest(email: email),
        );
        if (!mounted) return;
        if (result.success) {
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder: (_) => OtpVerifyScreen(
                email: email,
                useRegisterEndpoint: true,
              ),
            ),
          );
        } else {
          setState(() {
            _error = result.message.isEmpty ? "Failed to send code." : result.message;
            _sending = false;
          });
        }
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = "Failed to send code. Try again.";
        _sending = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 20),
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
                  child: const Icon(Icons.arrow_back, color: _kTextDark),
                ),
              ),
              const SizedBox(height: 40),
              Container(
                height: 60,
                width: 60,
                decoration: BoxDecoration(
                  color: _kPrimary.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: const Icon(Icons.mark_email_read, color: _kPrimary, size: 30),
              ),
              const SizedBox(height: 24),
              const Text(
                "Verify Your Email",
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: _kTextDark,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 12),
              const Text(
                "Your email has not been verified. Enter your email or phone to receive a new verification code.",
                style: TextStyle(
                  fontSize: 16,
                  color: _kTextLight,
                  fontWeight: FontWeight.w500,
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: TextButton(
                      onPressed: () => setState(() {
                        _usePhone = false;
                        _error = null;
                      }),
                      style: TextButton.styleFrom(
                        backgroundColor: !_usePhone
                            ? _kPrimary.withOpacity(0.15)
                            : Colors.transparent,
                        foregroundColor: !_usePhone ? _kPrimary : _kTextLight,
                      ),
                      child: const Text("Email"),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextButton(
                      onPressed: () => setState(() {
                        _usePhone = true;
                        _error = null;
                      }),
                      style: TextButton.styleFrom(
                        backgroundColor: _usePhone
                            ? _kPrimary.withOpacity(0.15)
                            : Colors.transparent,
                        foregroundColor: _usePhone ? _kPrimary : _kTextLight,
                      ),
                      child: const Text("Phone"),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              if (_error != null)
                Container(
                  width: double.infinity,
                  margin: const EdgeInsets.only(bottom: 16),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.red.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    _error!,
                    style: const TextStyle(
                      color: Colors.red,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              TextField(
                controller: _usePhone ? _phoneController : _emailController,
                keyboardType:
                    _usePhone ? TextInputType.phone : TextInputType.emailAddress,
                decoration: InputDecoration(
                  labelText: _usePhone ? "Phone number" : "Email address",
                  filled: true,
                  fillColor: const Color(0xFFF8F9FD),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(16),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: _sending ? null : _sendCode,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _kPrimary,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: _sending
                      ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 2,
                          ),
                        )
                      : const Text(
                          "Send verification code",
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
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
}
