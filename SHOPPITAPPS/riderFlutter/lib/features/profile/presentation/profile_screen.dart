import "package:file_picker/file_picker.dart";
import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../../auth/data/auth_service.dart";
import "../../auth/presentation/login_screen.dart";
import "../data/profile_service.dart";
import "../models/driver_profile.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  DriverProfile? _profile;
  bool _loading = true;
  bool _saving = false;
  bool _obscureCurrentPassword = true;
  bool _obscureNewPassword = true;
  bool _obscureConfirmPassword = true;
  String? _error;
  String? _statusMessage;
  Map<String, String> _fieldErrors = {};

  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _vehicleController = TextEditingController();
  final _licenseController = TextEditingController();
  final _fcmController = TextEditingController();
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _vehicleController.dispose();
    _licenseController.dispose();
    _fcmController.dispose();
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadProfile();
  }

  // -------------------------------------------------------------------------
  // LOGIC (KEPT INTACT)
  // -------------------------------------------------------------------------
  Future<void> _loadProfile() async {
    setState(() {
      _loading = true;
      _error = null;
      _statusMessage = null;
      _fieldErrors = {};
    });

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchProfile();
      if (!mounted) return;
      if (result.success && result.data != null) {
        _profile = result.data;
        _nameController.text = _profile!.user.name;
        _phoneController.text = _profile!.user.phone;
        _vehicleController.text = _profile!.vehicleType;
        _licenseController.text = _profile!.licenseNumber;
        if (_profile!.isOnline) {
          await AppScope.of(context).locationTracker.startTracking();
        }
      } else {
        _error = result.message.isEmpty
            ? "Failed to load profile."
            : result.message;
      }
    } catch (_) {
      _error = "Failed to load profile.";
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _saveProfile() async {
    if (_profile == null) return;

    setState(() {
      _saving = true;
      _statusMessage = null;
      _fieldErrors = {};
    });

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.updateProfile(
        DriverProfileUpdateRequest(
          name: _nameController.text.trim(),
          phone: _phoneController.text.trim(),
          vehicleType: _vehicleController.text.trim(),
          licenseNumber: _licenseController.text.trim(),
        ),
      );
      if (!mounted) return;
      if (result.success && result.data != null) {
        _profile = result.data;
        _statusMessage = "Profile updated.";
      } else {
        _statusMessage = result.message.isEmpty
            ? "Profile update failed."
            : result.message;
        _fieldErrors = result.fieldErrors;
      }
    } catch (_) {
      _statusMessage = "Profile update failed.";
    } finally {
      if (!mounted) return;
      setState(() => _saving = false);
    }
  }

  Future<void> _toggleOnline(bool value) async {
    setState(() => _statusMessage = null);
    _fieldErrors = {};

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.updateStatus(isOnline: value);
      if (!mounted) return;
      if (result.success && _profile != null) {
        if (value) {
          await AppScope.of(context).locationTracker.startTracking();
        } else {
          await AppScope.of(context).locationTracker.stopTracking();
        }
        setState(() {
          _profile = DriverProfile(
            user: _profile!.user,
            vehicleType: _profile!.vehicleType,
            licenseNumber: _profile!.licenseNumber,
            isVerified: _profile!.isVerified,
            isOnline: value,
          );
          _statusMessage = "Status updated.";
        });
      } else {
        setState(() {
          _statusMessage = result.message.isEmpty
              ? "Failed to update status."
              : result.message;
          _fieldErrors = result.fieldErrors;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _statusMessage = "Failed to update status.");
    }
  }

  Future<void> _registerFcmToken() async {
    final token = _fcmController.text.trim();
    if (token.isEmpty) {
      setState(() => _statusMessage = "Enter an FCM token.");
      return;
    }

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.registerFcmToken(token);
      if (!mounted) return;
      setState(() {
        _statusMessage = result.success
            ? "FCM token registered."
            : result.message;
        _fieldErrors = result.fieldErrors;
      });
    } catch (_) {
      setState(() => _statusMessage = "Failed to register FCM token.");
    }
  }

  Future<void> _uploadAvatar() async {
    final result = await FilePicker.platform.pickFiles(type: FileType.image);
    if (result == null || result.files.single.path == null) return;

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    setState(() => _statusMessage = null);

    try {
      final response = await service.updateAvatar(result.files.single.path!);
      if (!mounted) return;

      setState(() {
        _statusMessage = response.success
            ? "Avatar updated."
            : response.message.isEmpty
                ? "Avatar update failed."
                : response.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _statusMessage = "Avatar update failed.");
    }
  }

  Future<void> _changePassword() async {
    final currentPassword = _currentPasswordController.text;
    final newPassword = _newPasswordController.text;
    final confirmPassword = _confirmPasswordController.text;

    if (newPassword != confirmPassword) {
      setState(() {
        _statusMessage = null;
        _fieldErrors = {
          ..._fieldErrors,
          "password_confirmation": "Confirm password does not match new password",
        };
      });
      return;
    }

    setState(() {
      _fieldErrors = Map.from(_fieldErrors)..remove("password_confirmation");
    });

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    setState(() => _statusMessage = null);

    try {
      final response = await service.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
        confirmPassword: confirmPassword,
      );

      if (!mounted) return;

      setState(() {
        _statusMessage = response.success
            ? "Password updated."
            : response.message.isEmpty
                ? "Password update failed."
                : response.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _statusMessage = "Password update failed.");
    }
  }

  Future<void> _logout() async {
    final dependencies = AppScope.of(context);
    final authService = AuthService(
      apiClient: dependencies.apiClient,
      tokenStorage: dependencies.tokenStorage,
    );
    await authService.logout();

    await dependencies.locationTracker.stopTracking();

    if (!mounted) return;

    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  // -------------------------------------------------------------------------
  // UI BUILD
  // -------------------------------------------------------------------------
  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        backgroundColor: kBackgroundColor,
        body: Center(child: CircularProgressIndicator(color: kPrimaryColor)),
      );
    }

    if (_error != null) {
      return Scaffold(
        backgroundColor: kBackgroundColor,
        appBar: AppBar(backgroundColor: kBackgroundColor, elevation: 0),
        body: Center(child: Text(_error!, style: const TextStyle(color: kErrorColor))),
      );
    }

    if (_profile == null) {
      return const Scaffold(
        backgroundColor: kBackgroundColor,
        body: Center(child: Text("Profile not found.")),
      );
    }

    return Scaffold(
      backgroundColor: kBackgroundColor,
      appBar: AppBar(
        backgroundColor: kBackgroundColor,
        elevation: 0,
        centerTitle: true,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: kSurfaceColor,
              shape: BoxShape.circle,
              boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10)],
            ),
            child: const Icon(Icons.arrow_back, color: kTextDark, size: 20),
          ),
        ),
        title: const Text(
          "Profile",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
        actions: [
          IconButton(
            onPressed: _logout,
            icon: const Icon(Icons.logout_rounded, color: kErrorColor),
            tooltip: "Log Out",
          ),
        ],
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          physics: const BouncingScrollPhysics(),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // --- 1. Avatar & Status Hero ---
              _buildAvatarHero(),
              const SizedBox(height: 32),
              
              // Status Message (Feedback)
              if (_statusMessage != null) ...[
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: kPrimaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: kPrimaryColor.withOpacity(0.2)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.info_outline, color: kPrimaryColor, size: 20),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          _statusMessage!, 
                          style: const TextStyle(color: kPrimaryColor, fontWeight: FontWeight.bold)
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),
              ],

              // --- 2. Personal Details Form ---
              _buildSectionTitle("PERSONAL DETAILS"),
              _buildModernInput(
                controller: _nameController,
                label: "Full Name",
                icon: Icons.person_outline_rounded,
                errorText: _fieldErrors["name"],
              ),
              const SizedBox(height: 16),
              _buildModernInput(
                controller: _phoneController,
                label: "Phone Number",
                icon: Icons.phone_outlined,
                errorText: _fieldErrors["phone"],
              ),
              const SizedBox(height: 16),
              
              Row(
                children: [
                  Expanded(
                    child: _buildModernInput(
                      controller: _vehicleController,
                      label: "Vehicle",
                      icon: Icons.directions_car_outlined,
                      errorText: _fieldErrors["vehicle_type"],
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: _buildModernInput(
                      controller: _licenseController,
                      label: "License",
                      icon: Icons.badge_outlined,
                      errorText: _fieldErrors["license_number"],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              
              // Save Button
              SizedBox(
                width: double.infinity,
                height: 56,
                child: ElevatedButton(
                  onPressed: _saving ? null : _saveProfile,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kPrimaryColor,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                  ),
                  child: _saving 
                    ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text("Save Changes", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
              ),

              const SizedBox(height: 40),

              // --- 3. Security Section ---
              _buildSectionTitle("SECURITY"),
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: kSurfaceColor,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 15, offset: const Offset(0, 5)),
                  ],
                ),
                child: Column(
                  children: [
                    _buildSimpleInput(
                      _currentPasswordController,
                      "Current Password",
                      obscureText: _obscureCurrentPassword,
                      onToggleObscure: () => setState(() => _obscureCurrentPassword = !_obscureCurrentPassword),
                    ),
                    const SizedBox(height: 12),
                    _buildSimpleInput(
                      _newPasswordController,
                      "New Password",
                      obscureText: _obscureNewPassword,
                      onToggleObscure: () => setState(() => _obscureNewPassword = !_obscureNewPassword),
                    ),
                    const SizedBox(height: 12),
                    _buildSimpleInput(
                      _confirmPasswordController,
                      "Confirm Password",
                      obscureText: _obscureConfirmPassword,
                      onToggleObscure: () => setState(() => _obscureConfirmPassword = !_obscureConfirmPassword),
                      errorText: _fieldErrors["password_confirmation"],
                    ),
                    const SizedBox(height: 20),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton(
                        onPressed: _changePassword,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: kTextDark,
                          side: BorderSide(color: kTextLight.withOpacity(0.5)),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                        ),
                        child: const Text("Update Password"),
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 40),

              // --- 4. Advanced/Notifications ---
              Theme(
                data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                child: ExpansionTile(
                  title: const Text("Advanced Settings", style: TextStyle(color: kTextLight, fontWeight: FontWeight.w600)),
                  children: [
                    _buildModernInput(
                      controller: _fcmController,
                      label: "FCM Token",
                      icon: Icons.notifications_none,
                      errorText: _fieldErrors["fcm_device_token"],
                    ),
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: _registerFcmToken,
                      child: const Text("Update Token", style: TextStyle(color: kPrimaryColor)),
                    )
                  ],
                ),
              ),
              const SizedBox(height: 40),
            ],
          ),
        ),
      ),
    );
  }

  // -------------------------------------------------------------------------
  // HELPER WIDGETS
  // -------------------------------------------------------------------------

  Widget _buildAvatarHero() {
    return Center(
      child: Column(
        children: [
          Stack(
            children: [
              Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  boxShadow: [
                    BoxShadow(
                      color: kPrimaryColor.withOpacity(0.2),
                      blurRadius: 20,
                      offset: const Offset(0, 10),
                    ),
                  ],
                  border: Border.all(color: kSurfaceColor, width: 4),
                ),
                child: CircleAvatar(
                  radius: 60,
                  backgroundColor: kTextLight.withOpacity(0.1),
                  backgroundImage: _profile!.user.avatar != null && _profile!.user.avatar!.isNotEmpty
                      ? NetworkImage(_profile!.user.avatar!)
                      : null,
                  child: _profile!.user.avatar == null || _profile!.user.avatar!.isEmpty
                      ? const Icon(Icons.person, size: 60, color: kTextLight)
                      : null,
                ),
              ),
              Positioned(
                bottom: 0,
                right: 0,
                child: GestureDetector(
                  onTap: _uploadAvatar,
                  child: Container(
                    padding: const EdgeInsets.all(8),
                    decoration: const BoxDecoration(
                      color: kPrimaryColor,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 20),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            _profile!.user.email,
            style: const TextStyle(fontSize: 14, color: kTextLight, fontWeight: FontWeight.w500),
          ),
          const SizedBox(height: 12),
          
          // Verification & Online Status Row
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Verification Badge
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: _profile!.isVerified ? kPrimaryColor.withOpacity(0.1) : Colors.orange.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Row(
                  children: [
                    Icon(
                      _profile!.isVerified ? Icons.verified : Icons.warning_amber_rounded,
                      size: 14,
                      color: _profile!.isVerified ? kPrimaryColor : Colors.orange,
                    ),
                    const SizedBox(width: 6),
                    Text(
                      _profile!.isVerified ? "Verified" : "Unverified",
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.bold,
                        color: _profile!.isVerified ? kPrimaryColor : Colors.orange,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              
              // Online Toggle (Compact)
              GestureDetector(
                onTap: () => _toggleOnline(!_profile!.isOnline),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 300),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: _profile!.isOnline ? kPrimaryColor : kTextLight.withOpacity(0.3),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
                      Container(
                        width: 8, height: 8,
                        decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        _profile!.isOnline ? "Online" : "Offline",
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16, left: 4),
      child: Text(
        title,
        style: const TextStyle(
          color: kTextLight,
          fontSize: 12,
          fontWeight: FontWeight.w800,
          letterSpacing: 1.2,
        ),
      ),
    );
  }

  Widget _buildModernInput({
    required TextEditingController controller,
    required String label,
    required IconData icon,
    String? errorText,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.15),
            blurRadius: 15,
            offset: const Offset(0, 5),
          ),
        ],
      ),
      child: TextField(
        controller: controller,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight, fontSize: 14),
          prefixIcon: Icon(icon, color: kTextLight, size: 22),
          errorText: errorText,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        ),
      ),
    );
  }

  Widget _buildSimpleInput(
    TextEditingController controller,
    String label, {
    bool obscureText = true,
    VoidCallback? onToggleObscure,
    String? errorText,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: kBackgroundColor,
        borderRadius: BorderRadius.circular(12),
      ),
      child: TextField(
        controller: controller,
        obscureText: obscureText,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight, fontSize: 14),
          errorText: errorText,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          suffixIcon: onToggleObscure != null
              ? IconButton(
                  icon: Icon(
                    obscureText ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                    color: kTextLight,
                    size: 22,
                  ),
                  onPressed: onToggleObscure,
                )
              : null,
        ),
      ),
    );
  }
}