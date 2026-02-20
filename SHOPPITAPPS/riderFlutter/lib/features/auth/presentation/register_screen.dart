import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/auth_service.dart";
import "../models/auth_models.dart";
import "otp_verify_screen.dart";

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _vehicleController = TextEditingController();
  final _licenseController = TextEditingController();
  final _fcmController = TextEditingController();

  bool _submitting = false;
  String? _error;
  Map<String, String> _fieldErrors = {};
  bool _verifyWithPhone = false;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _vehicleController.dispose();
    _licenseController.dispose();
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

      final result = await service.register(
        RegisterRequest(
          name: _nameController.text.trim(),
          email: _emailController.text.trim(),
          phone: _phoneController.text.trim(),
          password: _passwordController.text,
          vehicleType: _vehicleController.text.trim(),
          licenseNumber: _licenseController.text.trim(),
          fcmDeviceToken: _fcmController.text.trim().isEmpty
              ? null
              : _fcmController.text.trim(),
        ),
      );

      if (!mounted) return;

      if (result.success) {
        if (_verifyWithPhone) {
          final phone = _phoneController.text.trim();
          if (phone.isEmpty) {
            setState(() => _error = "Phone number is required for OTP.");
            return;
          }

          final otpResult = await service.sendOtp(OtpSendRequest(phone: phone));
          if (!mounted) return;

          if (!otpResult.success) {
            setState(() => _error = otpResult.message.isEmpty
                ? "Failed to send OTP."
                : otpResult.message);
            return;
          }

          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder: (_) => OtpVerifyScreen(
                phone: phone,
                useRegisterEndpoint: false,
              ),
            ),
          );
          return;
        }

        Navigator.of(context).pushReplacement(
          MaterialPageRoute(
            builder: (_) => OtpVerifyScreen(email: _emailController.text.trim()),
          ),
        );
      } else {
        setState(() {
          _error = result.message.isEmpty
              ? "Registration failed."
              : result.message;
          _fieldErrors = result.fieldErrors;
        });
      }
    } catch (e) {
      if (!mounted) return;
      setState(() => _error = "Registration failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Driver Register")),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Create account",
                      style: TextStyle(fontSize: 22, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      "Complete your driver details",
                      style: TextStyle(color: Color(0xFF757575)),
                    ),
                    const SizedBox(height: 16),
                    if (_error != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          _error!,
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    TextFormField(
                      controller: _nameController,
                      decoration: const InputDecoration(labelText: "Full name"),
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["name"];
                        if (serverError != null) return serverError;
                        if (value == null || value.trim().isEmpty) {
                          return "Enter your name";
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _emailController,
                      decoration: const InputDecoration(labelText: "Email"),
                      keyboardType: TextInputType.emailAddress,
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["email"];
                        if (serverError != null) return serverError;
                        if (value == null || value.trim().isEmpty) {
                          return "Enter your email";
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(12),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Verify account with",
                              style: TextStyle(fontWeight: FontWeight.w600),
                            ),
                            const SizedBox(height: 8),
                            ToggleButtons(
                              isSelected: [_verifyWithPhone == false, _verifyWithPhone == true],
                              onPressed: (index) {
                                setState(() => _verifyWithPhone = index == 1);
                              },
                              children: const [
                                Padding(
                                  padding: EdgeInsets.symmetric(horizontal: 12),
                                  child: Text("Email"),
                                ),
                                Padding(
                                  padding: EdgeInsets.symmetric(horizontal: 12),
                                  child: Text("Phone"),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              _verifyWithPhone
                                  ? "We will send OTP to your phone after signup."
                                  : "We will send OTP to your email after signup.",
                              style: const TextStyle(color: Color(0xFF757575)),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _phoneController,
                      decoration: const InputDecoration(labelText: "Phone"),
                      keyboardType: TextInputType.phone,
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["phone"];
                        if (serverError != null) return serverError;
                        if (value == null || value.trim().isEmpty) {
                          return "Enter your phone";
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _vehicleController,
                      decoration: const InputDecoration(labelText: "Vehicle type"),
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["vehicle_type"];
                        if (serverError != null) return serverError;
                        if (value == null || value.trim().isEmpty) {
                          return "Enter vehicle type";
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _licenseController,
                      decoration: const InputDecoration(labelText: "License number"),
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["license_number"];
                        if (serverError != null) return serverError;
                        if (value == null || value.trim().isEmpty) {
                          return "Enter license number";
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _passwordController,
                      decoration: const InputDecoration(labelText: "Password"),
                      obscureText: true,
                      autovalidateMode: AutovalidateMode.onUserInteraction,
                      validator: (value) {
                        final serverError = _fieldErrors["password"];
                        if (serverError != null) return serverError;
                        if (value == null || value.length < 8) {
                          return "Password must be at least 8 characters";
                        }
                        return null;
                      },
                    ),
                    
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton(
                        onPressed: _submitting ? null : _submit,
                        child: Text(
                          _submitting ? "Creating..." : "Create account",
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
