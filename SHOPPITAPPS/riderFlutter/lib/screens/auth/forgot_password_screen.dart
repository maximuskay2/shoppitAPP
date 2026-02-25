import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../utils/validators.dart';
import '../../widgets/custom_button.dart';
import '../../widgets/custom_text_field.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({Key? key}) : super(key: key);

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _otpController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isLoading = false;
  bool _otpSent = false;
  String? _errorMessage;

  @override
  void dispose() {
    _emailController.dispose();
    _otpController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _requestOtp() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final success = await authProvider.requestPasswordReset(_emailController.text.trim());

      if (success) {
        setState(() {
          _otpSent = true;
        });
        _showSnackBar('Reset code sent to your email');
      } else {
        setState(() {
          _errorMessage = authProvider.error ?? 'Failed to send reset code';
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _resetPassword() async {
    if (!_formKey.currentState!.validate()) return;

    if (_passwordController.text != _confirmPasswordController.text) {
      setState(() {
        _errorMessage = 'Passwords do not match';
      });
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final success = await authProvider.resetPassword(
        email: _emailController.text.trim(),
        otp: _otpController.text.trim(),
        password: _passwordController.text,
      );

      if (success) {
        _showSnackBar('Password reset successfully!');
        Navigator.of(context).pop();
      } else {
        setState(() {
          _errorMessage = authProvider.error ?? 'Failed to reset password';
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Reset Password'),
        elevation: 0,
        backgroundColor: Colors.transparent,
        foregroundColor: Theme.of(context).primaryColor,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Icon
                Icon(
                  _otpSent ? Icons.lock_reset : Icons.email_outlined,
                  size: 80,
                  color: Theme.of(context).primaryColor,
                ),
                const SizedBox(height: 24),

                // Title
                Text(
                  _otpSent ? 'Enter Reset Code' : 'Forgot Password?',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 8),

                // Subtitle
                Text(
                  _otpSent
                      ? 'Enter the code sent to your email and create a new password'
                      : 'Enter your email address and we\'ll send you a code to reset your password',
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: Colors.grey[600],
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32),

                // Error message
                if (_errorMessage != null)
                  Container(
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: Colors.red[50],
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.red[200]!),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.error_outline, color: Colors.red[700], size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            _errorMessage!,
                            style: TextStyle(color: Colors.red[700]),
                          ),
                        ),
                      ],
                    ),
                  ),

                // Email field (always visible)
                CustomTextField(
                  controller: _emailController,
                  label: 'Email',
                  hint: 'Enter your email',
                  keyboardType: TextInputType.emailAddress,
                  prefixIcon: Icons.email_outlined,
                  enabled: !_otpSent,
                  validator: Validators.validateEmail,
                ),
                const SizedBox(height: 16),

                if (_otpSent) ...[
                  // OTP field
                  CustomTextField(
                    controller: _otpController,
                    label: 'Reset Code',
                    hint: 'Enter the 6-digit code',
                    keyboardType: TextInputType.number,
                    prefixIcon: Icons.pin,
                    maxLength: 6,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please enter the reset code';
                      }
                      if (value.length < 4) {
                        return 'Invalid code';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),

                  // New password
                  CustomTextField(
                    controller: _passwordController,
                    label: 'New Password',
                    hint: 'Enter new password',
                    obscureText: true,
                    prefixIcon: Icons.lock_outline,
                    validator: Validators.validatePassword,
                  ),
                  const SizedBox(height: 16),

                  // Confirm password
                  CustomTextField(
                    controller: _confirmPasswordController,
                    label: 'Confirm Password',
                    hint: 'Confirm new password',
                    obscureText: true,
                    prefixIcon: Icons.lock_outline,
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Please confirm your password';
                      }
                      if (value != _passwordController.text) {
                        return 'Passwords do not match';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 8),

                  // Password requirements
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Password must contain:',
                          style: TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            color: Colors.grey[700],
                          ),
                        ),
                        const SizedBox(height: 4),
                        _buildRequirement('At least 8 characters'),
                        _buildRequirement('Uppercase letter'),
                        _buildRequirement('Lowercase letter'),
                        _buildRequirement('Number'),
                        _buildRequirement('Special character'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Resend code button
                  TextButton(
                    onPressed: _isLoading ? null : _requestOtp,
                    child: const Text('Resend Code'),
                  ),
                ],

                const SizedBox(height: 24),

                // Submit button
                CustomButton(
                  text: _otpSent ? 'Reset Password' : 'Send Reset Code',
                  onPressed: _isLoading ? null : (_otpSent ? _resetPassword : _requestOtp),
                  isLoading: _isLoading,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildRequirement(String text) {
    return Padding(
      padding: const EdgeInsets.only(top: 2),
      child: Row(
        children: [
          Icon(Icons.check_circle_outline, size: 14, color: Colors.grey[600]),
          const SizedBox(width: 4),
          Text(
            text,
            style: TextStyle(fontSize: 11, color: Colors.grey[600]),
          ),
        ],
      ),
    );
  }
}
