import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../../home/presentation/home_shell.dart";
import "../../profile/data/profile_service.dart";
import "document_upload_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kPendingColor = Color(0xFFFFA000); // Amber for pending
const Color kBackgroundColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);

class VerificationStatusScreen extends StatefulWidget {
  const VerificationStatusScreen({
    super.key,
    required this.isVerified,
    required this.statusLabel,
  });

  final bool isVerified;
  final String statusLabel;

  @override
  State<VerificationStatusScreen> createState() =>
      _VerificationStatusScreenState();
}

class _VerificationStatusScreenState extends State<VerificationStatusScreen> {
  late bool _isVerified;
  late String _statusLabel;
  bool _checking = false;
  bool _navigated = false;
  bool _didInit = false;

  @override
  void initState() {
    super.initState();
    _isVerified = widget.isVerified;
    _statusLabel = widget.statusLabel;
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_didInit) return;
    _didInit = true;
    _refreshStatus();
  }

  Future<void> _refreshStatus() async {
    if (_checking) return;
    setState(() => _checking = true);
    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchProfile();
      if (!mounted || _navigated) return;
      final isVerified = result.success && result.data?.isVerified == true;
      setState(() {
        _isVerified = isVerified;
        _statusLabel = isVerified ? "Verified" : "Pending verification";
      });
      if (isVerified) {
        _navigated = true;
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const HomeShell()),
          (route) => false,
        );
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _statusLabel = "Pending verification");
    } finally {
      if (!mounted) return;
      setState(() => _checking = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Determine visuals based on status
    final statusColor = _isVerified ? kPrimaryColor : kPendingColor;
    final statusIcon = _isVerified
        ? Icons.verified_user_rounded
        : Icons.pending_actions_rounded;

    return Scaffold(
      backgroundColor: kBackgroundColor,
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: Column(
            children: [
              // --- 1. Top Bar (Back Button) ---
              Align(
                alignment: Alignment.topLeft,
                child: GestureDetector(
                  onTap: () => Navigator.of(context).pop(),
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
              ),

              const Spacer(), 

              // --- 2. 3D Hero Status Icon ---
              Container(
                height: 140,
                width: 140,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: Colors.white,
                  boxShadow: [
                    // Outer glow matching status color
                    BoxShadow(
                      color: statusColor.withOpacity(0.15),
                      blurRadius: 30,
                      spreadRadius: 5,
                      offset: const Offset(0, 10),
                    ),
                    // Inner depth shadow
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 10,
                      offset: const Offset(0, 5),
                    ),
                  ],
                ),
                child: Center(
                  child: Icon(
                    statusIcon,
                    size: 64,
                    color: statusColor,
                  ),
                ),
              ),

              const SizedBox(height: 40),

              // --- 3. Status Text ---
              Text(
                _statusLabel,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: kTextDark,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Text(
                  _isVerified
                      ? "You are cleared to start delivering."
                      : "We are reviewing your documents. You can upload missing documents anytime.",
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 16,
                    color: kTextLight,
                    height: 1.5,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),

              const Spacer(flex: 2),

              // --- 4. Primary Action Button ---
              if (!_isVerified)
                _build3DButton(
                  context: context,
                  label: "Upload Documents",
                  onPressed: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(
                        builder: (_) => const DocumentUploadScreen(),
                      ),
                    );
                  },
                ),

              if (_isVerified)
                _build3DButton(
                  context: context,
                  label: "Go to Dashboard",
                  onPressed: () {
                    Navigator.of(context).pushAndRemoveUntil(
                      MaterialPageRoute(builder: (_) => const HomeShell()),
                      (route) => false,
                    );
                  },
                ),
              if (!_isVerified)
                TextButton(
                  onPressed: _checking ? null : _refreshStatus,
                  child: Text(_checking ? "Checking..." : "Refresh status"),
                ),
              
              const SizedBox(height: 20),
            ],
          ),
        ),
      ),
    );
  }

  // Helper for the Green 3D Button
  Widget _build3DButton({
    required BuildContext context,
    required String label,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      width: double.infinity,
      height: 56,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          padding: EdgeInsets.zero,
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
          ),
        ),
      ),
    );
  }
}