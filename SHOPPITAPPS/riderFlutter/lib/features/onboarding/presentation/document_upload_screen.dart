import "package:dio/dio.dart";
import "package:file_picker/file_picker.dart";
import "package:flutter/material.dart";
import "package:image_picker/image_picker.dart";
import "package:permission_handler/permission_handler.dart";

import "../../../app/app_scope.dart";
import "../data/document_service.dart";
import "../models/driver_document.dart";
import "verification_status_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFFFFFFF);
const Color kInputFillColor = Color(0xFFF8F9FD);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);
const Color kWarningColor = Color(0xFFFFA000);

class DocumentUploadScreen extends StatefulWidget {
  const DocumentUploadScreen({super.key});

  @override
  State<DocumentUploadScreen> createState() => _DocumentUploadScreenState();
}

class _DocumentUploadScreenState extends State<DocumentUploadScreen> {
  bool _submitting = false;
  String? _statusMessage;
  bool _showOpenSettings = false;
  Map<String, String> _fieldErrors = {};
  bool _loadingDocs = true;
  List<DriverDocument> _documents = [];
  static const List<String> _requiredTypes = [
    "drivers_license",
    "vehicle_registration",
    "insurance",
    "government_id",
  ];

  bool _loadDocsRun = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_loadDocsRun) {
      _loadDocsRun = true;
      _loadDocuments();
    }
  }

  Future<void> _loadDocuments() async {
    setState(() => _loadingDocs = true);
    final service = DocumentService(apiClient: AppScope.of(context).apiClient);
    try {
      final response = await service.fetchDocuments();
      if (!mounted) return;
      setState(() {
        _documents = response.data ?? [];
        _loadingDocs = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingDocs = false);
    }
  }

  void _showUploadSourceChooser(String type) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
        ),
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 20),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                "Upload document",
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: kTextDark,
                ),
              ),
              const SizedBox(height: 20),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: kPrimaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.camera_alt, color: kPrimaryColor, size: 24),
                ),
                title: const Text(
                  "Take Photo",
                  style: TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
                ),
                subtitle: const Text(
                  "Use your camera to capture the document",
                  style: TextStyle(fontSize: 12, color: kTextLight),
                ),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickAndUploadFromCamera(type);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: kPrimaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.photo_library, color: kPrimaryColor, size: 24),
                ),
                title: const Text(
                  "Choose from Gallery",
                  style: TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
                ),
                subtitle: const Text(
                  "Select an existing photo",
                  style: TextStyle(fontSize: 12, color: kTextLight),
                ),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickAndUploadFromGallery(type);
                },
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: kPrimaryColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.folder_open, color: kPrimaryColor, size: 24),
                ),
                title: const Text(
                  "Choose File",
                  style: TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
                ),
                subtitle: const Text(
                  "JPG, PNG or PDF",
                  style: TextStyle(fontSize: 12, color: kTextLight),
                ),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickAndUploadFromFiles(type);
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _pickAndUploadFromCamera(String type) async {
    final status = await Permission.camera.request();
    if (!status.isGranted) {
      if (mounted) {
        final msg = status.isPermanentlyDenied
            ? "Camera access was denied. Enable it in settings to take photos."
            : "Camera permission is needed to take photos. Please allow when prompted.";
        setState(() {
          _statusMessage = msg;
          _showOpenSettings = status.isPermanentlyDenied;
        });
      }
      return;
    }
    final picker = ImagePicker();
    final xFile = await picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
    );
    if (xFile == null || !mounted) return;
    await _doUpload(type, xFile.path);
  }

  Future<void> _pickAndUploadFromGallery(String type) async {
    final status = await Permission.photos.request();
    if (!status.isGranted) {
      if (mounted) {
        final msg = status.isPermanentlyDenied
            ? "Photo access was denied. Enable it in settings to select images."
            : "Photo library permission is needed to select images. Please allow when prompted.";
        setState(() {
          _statusMessage = msg;
          _showOpenSettings = status.isPermanentlyDenied;
        });
      }
      return;
    }
    final picker = ImagePicker();
    final xFile = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 85,
    );
    if (xFile == null || !mounted) return;
    await _doUpload(type, xFile.path);
  }

  Future<void> _pickAndUploadFromFiles(String type) async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const ["jpg", "jpeg", "png", "pdf"],
    );
    if (result == null || result.files.single.path == null || !mounted) return;
    await _doUpload(type, result.files.single.path!);
  }

  String _extractUploadError(Object e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map && data["message"] != null) {
        return data["message"].toString();
      }
      switch (e.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return "Request timed out. Check your connection and try again.";
        case DioExceptionType.connectionError:
          return "Could not reach server. Ensure the API is running and reachable.";
        case DioExceptionType.badResponse:
          return "Upload failed. Try again.";
        default:
          return e.message ?? "Upload failed. Try again.";
      }
    }
    return "Upload failed. Try again.";
  }

  Future<void> _doUpload(String type, String filePath) async {
    setState(() {
      _submitting = true;
      _statusMessage = null;
      _fieldErrors = {};
      _showOpenSettings = false;
    });

    final service = DocumentService(apiClient: AppScope.of(context).apiClient);
    try {
      final response = await service.uploadDocument(
        documentType: type,
        filePath: filePath,
      );

      if (!mounted) return;

      if (response.success) {
        setState(() {
          _statusMessage = "Upload successful.";
          _showOpenSettings = false;
        });
        _loadDocuments();
      } else {
        setState(() {
          _statusMessage = response.message.isEmpty
              ? "Upload failed."
              : response.message;
          _fieldErrors = response.fieldErrors;
          _showOpenSettings = false;
        });
      }
    } catch (e, st) {
      if (!mounted) return;
      final String message = _extractUploadError(e);
      debugPrint("Document upload error: $e\n$st");
      setState(() {
        _statusMessage = message;
        _showOpenSettings = false;
      });
    } finally {
      if (!mounted) return;
      setState(() => _submitting = false);
    }
  }

  void _submit() {
    final missing = _requiredTypes.where((type) {
      return !_documents.any((doc) => doc.documentType == type && doc.id.isNotEmpty);
    }).toList();

    if (missing.isNotEmpty) {
      setState(() => _statusMessage =
          "Please upload all required documents before submitting.");
      return;
    }

    final rejectedDocs = _documents
        .where((doc) => doc.status.toUpperCase() == "REJECTED")
        .toList();
    if (rejectedDocs.isNotEmpty) {
      setState(() => _statusMessage =
          "Some documents were rejected. Please re-upload them.");
      return;
    }

    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (_) => const VerificationStatusScreen(
          isVerified: false,
          statusLabel: "Pending review",
        ),
      ),
    );
  }

  Widget _docTile(String title, String subtitle, String typeKey) {
    final doc = _documents.firstWhere(
      (item) => item.documentType == typeKey,
      orElse: () => const DriverDocument(
        id: "",
        documentType: "",
        fileUrl: "",
        status: "",
      ),
    );
    final hasDoc = doc.id.isNotEmpty;
    final isRejected = doc.status.toUpperCase() == "REJECTED";
    final isApproved = doc.status.toUpperCase() == "APPROVED";

    Color borderColor = Colors.transparent;
    if (isRejected) borderColor = kErrorColor.withOpacity(0.5);
    if (isApproved) borderColor = kPrimaryColor.withOpacity(0.5);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: borderColor, width: isRejected || isApproved ? 1.5 : 0),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.15),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: _submitting ? null : () => _showUploadSourceChooser(typeKey),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    // Icon Container
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: hasDoc 
                          ? (isRejected ? kErrorColor.withOpacity(0.1) : kPrimaryColor.withOpacity(0.1))
                          : kInputFillColor,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        isApproved ? Icons.check_circle : Icons.upload_file,
                        color: hasDoc 
                          ? (isRejected ? kErrorColor : kPrimaryColor)
                          : kTextLight,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 16),
                    
                    // Text Details
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            style: const TextStyle(
                              fontWeight: FontWeight.w700,
                              color: kTextDark,
                              fontSize: 15,
                            ),
                          ),
                          const SizedBox(height: 4),
                          if (hasDoc)
                            _statusBadge(doc.status)
                          else
                            Text(
                              subtitle,
                              style: const TextStyle(color: kTextLight, fontSize: 13),
                            ),
                        ],
                      ),
                    ),

                    // Action Button (Upload)
                    if (!isApproved)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                      decoration: BoxDecoration(
                        color: kPrimaryColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.cloud_upload_outlined, color: kPrimaryColor, size: 20),
                    ),
                  ],
                ),
                
                // Rejection Reason Area
                if (doc.rejectionReason != null && doc.rejectionReason!.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: kErrorColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        "Reason: ${doc.rejectionReason}",
                        style: const TextStyle(color: kErrorColor, fontSize: 12, fontWeight: FontWeight.w600),
                      ),
                    ),
                  ),

                // Field Errors
                if (_fieldErrors["document_type"] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      _fieldErrors["document_type"]!,
                      style: const TextStyle(color: kErrorColor, fontSize: 12),
                    ),
                  ),
                if (_fieldErrors["document"] != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      _fieldErrors["document"]!,
                      style: const TextStyle(color: kErrorColor, fontSize: 12),
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      body: SafeArea(
        child: Column(
          children: [
            // --- 1. Fixed Header ---
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
              child: Row(
                children: [
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
                  const SizedBox(width: 20),
                  const Text(
                    "Documents",
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.w800,
                      color: kTextDark,
                    ),
                  ),
                ],
              ),
            ),

            // --- 2. Scrollable Content ---
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                physics: const BouncingScrollPhysics(),
                children: [
                  const Text(
                    "Upload Required Files",
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: kTextDark),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    "Ensure photos are clear and text is readable.",
                    style: TextStyle(color: kTextLight, fontSize: 14),
                  ),
                  const SizedBox(height: 20),

                  _buildVerificationSummary(),

                  const SizedBox(height: 12),

                  if (_statusMessage != null)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 20),
                      decoration: BoxDecoration(
                        color: kErrorColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.info_outline, color: kErrorColor, size: 20),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  _statusMessage!,
                                  style: const TextStyle(color: kErrorColor, fontWeight: FontWeight.w600),
                                ),
                              ),
                            ],
                          ),
                          if (_showOpenSettings)
                            Padding(
                              padding: const EdgeInsets.only(top: 10),
                              child: TextButton.icon(
                                onPressed: () async {
                                  await openAppSettings();
                                },
                                icon: const Icon(Icons.settings, size: 18, color: kPrimaryColor),
                                label: const Text("Open Settings", style: TextStyle(color: kPrimaryColor, fontWeight: FontWeight.w600)),
                              ),
                            ),
                        ],
                      ),
                    ),
                  
                  const SizedBox(height: 16),
                  _docTile("Driver license", "Front and back", "drivers_license"),
                  _docTile("Vehicle registration", "Current registration card", "vehicle_registration"),
                  _docTile("Insurance", "Insurance certificate", "insurance"),
                  _docTile("Government ID", "National ID or passport", "government_id"),
                  
                  const SizedBox(height: 100), // Space for FAB
                ],
              ),
            ),
          ],
        ),
      ),
      
      // --- 3. Floating Action Button ---
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24),
        child: SizedBox(
          width: double.infinity,
          height: 56,
          child: ElevatedButton(
            onPressed: _submitting ? null : _submit,
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
                        "Submit Documents",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildVerificationSummary() {
    if (_loadingDocs) {
      return Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: kInputFillColor,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
      );
    }

    final status = _overallVerificationStatus();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: status.color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: status.color.withOpacity(0.3)),
      ),
      child: Column(
        children: [
          Icon(status.icon, size: 32, color: status.color),
          const SizedBox(height: 12),
          Text(
            status.title,
            style: TextStyle(
              fontSize: 16, 
              fontWeight: FontWeight.bold, 
              color: status.color
            ),
          ),
          const SizedBox(height: 4),
          Text(
            status.message,
            textAlign: TextAlign.center,
            style: TextStyle(
              fontSize: 13, 
              color: kTextDark.withOpacity(0.7)
            ),
          ),
        ],
      ),
    );
  }

  _VerificationStatus _overallVerificationStatus() {
    if (_documents.isEmpty) {
      return const _VerificationStatus(
        label: "Not submitted",
        title: "No Documents Uploaded",
        message: "Upload all required documents to start the verification process.",
        color: kTextLight,
        icon: Icons.cloud_upload_outlined,
      );
    }

    final hasRejected = _documents.any(
      (doc) => doc.status.toUpperCase() == "REJECTED",
    );
    if (hasRejected) {
      return const _VerificationStatus(
        label: "Action required",
        title: "Action Required",
        message: "Some documents were rejected. Please check the details above.",
        color: kErrorColor,
        icon: Icons.error_outline_rounded,
      );
    }

    final allApproved = _documents.isNotEmpty &&
        _documents.every((doc) => doc.status.toUpperCase() == "APPROVED");
    if (allApproved) {
      return const _VerificationStatus(
        label: "Verified",
        title: "You are Verified!",
        message: "You are cleared to receive orders.",
        color: kPrimaryColor,
        icon: Icons.verified_rounded,
      );
    }

    return const _VerificationStatus(
      label: "Pending",
      title: "Under Review",
      message: "Our team is reviewing your documents. This usually takes 24 hours.",
      color: kWarningColor,
      icon: Icons.access_time_filled_rounded,
    );
  }

  Widget _statusBadge(String status) {
    final normalized = status.toUpperCase();
    Color color;
    if (normalized.contains("APPROV")) {
      color = kPrimaryColor;
    } else if (normalized.contains("REJECT")) {
      color = kErrorColor;
    } else if (normalized.contains("PEND")) {
      color = kWarningColor;
    } else {
      color = kTextLight;
    }

    return Text(
      _formatStatus(status),
      style: TextStyle(
        fontSize: 12,
        fontWeight: FontWeight.w700,
        color: color,
      ),
    );
  }

  String _formatStatus(String status) {
    if (status.isEmpty) return "Pending Upload";
    final formatted = status.replaceAll("_", " ").toLowerCase();
    return formatted[0].toUpperCase() + formatted.substring(1);
  }
}

class _VerificationStatus {
  const _VerificationStatus({
    required this.label,
    required this.title,
    required this.message,
    required this.color,
    required this.icon,
  });

  final String label;
  final String title;
  final String message;
  final Color color;
  final IconData icon;
}