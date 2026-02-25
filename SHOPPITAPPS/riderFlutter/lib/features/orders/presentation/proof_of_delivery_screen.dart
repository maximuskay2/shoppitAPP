import "dart:io";
import "dart:typed_data";

import "package:file_picker/file_picker.dart";
import "package:flutter/material.dart";
import "package:path_provider/path_provider.dart";
import "package:signature/signature.dart";

import "../../../app/app_scope.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";
import "../../home/presentation/home_shell.dart";

class ProofOfDeliveryScreen extends StatefulWidget {
  const ProofOfDeliveryScreen({
    super.key,
    required this.order,
  });

  final DriverOrder order;

  @override
  State<ProofOfDeliveryScreen> createState() => _ProofOfDeliveryScreenState();
}

class _ProofOfDeliveryScreenState extends State<ProofOfDeliveryScreen> {
  final _otpController = TextEditingController();
  final SignatureController _signatureController = SignatureController(
    penStrokeWidth: 2,
    penColor: Colors.black,
  );
  bool _submitting = false;
  String? _photoPath;
  String? _signaturePath;
  String? _uploadError;

  @override
  void dispose() {
    _otpController.dispose();
    _signatureController.dispose();
    super.dispose();
  }

  void _complete() {
    _submitDelivery();
  }

  Future<void> _submitDelivery() async {
    setState(() => _submitting = true);
    final service = OrderService(apiClient: AppScope.of(context).apiClient);

    if (_photoPath != null || _signaturePath != null) {
      final uploadResult = await service.uploadProofOfDelivery(
        widget.order.id,
        photoPath: _photoPath,
        signaturePath: _signaturePath,
      );

      if (!mounted) return;

      if (!uploadResult.success) {
        setState(() {
          _uploadError = uploadResult.message.isEmpty
              ? "Proof upload failed."
              : uploadResult.message;
          _submitting = false;
        });
        return;
      }
    }

    final result = await service.deliverOrder(
      widget.order.id,
      otpCode: _otpController.text.trim().isEmpty
          ? null
          : _otpController.text.trim(),
    );

    if (!mounted) return;

    if (result.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Delivery completed")),
      );
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => const HomeShell(initialIndex: 1),
        ),
        (route) => false,
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result.message.isEmpty ? "Delivery failed." : result.message,
          ),
        ),
      );
    }

    setState(() => _submitting = false);
  }

  Future<void> _pickPhoto() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.image,
    );

    if (result == null || result.files.single.path == null) return;

    setState(() {
      _photoPath = result.files.single.path;
      _uploadError = null;
    });
  }

  Future<void> _pickSignature() async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 16,
            right: 16,
            top: 16,
            bottom: 16 + MediaQuery.of(context).viewInsets.bottom,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                "Capture signature",
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 12),
              Container(
                height: 200,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: Colors.grey.shade300),
                ),
                child: Signature(
                  controller: _signatureController,
                  backgroundColor: Colors.white,
                ),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () => _signatureController.clear(),
                      child: const Text("Clear"),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: FilledButton(
                      onPressed: _saveSignature,
                      child: const Text("Save"),
                    ),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _saveSignature() async {
    if (_signatureController.isEmpty) {
      setState(() => _uploadError = "Please draw a signature.");
      return;
    }

    final bytes = await _signatureController.toPngBytes();
    if (bytes == null || bytes.isEmpty) {
      setState(() => _uploadError = "Failed to capture signature.");
      return;
    }

    final file = await _writeTempFile(bytes, "signature_${widget.order.id}.png");
    if (file == null) {
      setState(() => _uploadError = "Failed to save signature.");
      return;
    }

    if (!mounted) return;
    setState(() {
      _signaturePath = file.path;
      _uploadError = null;
    });
    Navigator.of(context).pop();
  }

  Future<File?> _writeTempFile(Uint8List bytes, String filename) async {
    try {
      final dir = await getTemporaryDirectory();
      final file = File("${dir.path}/$filename");
      await file.writeAsBytes(bytes, flush: true);
      return file;
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Proof of delivery")),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: ListTile(
                title: Text(widget.order.receiverName ?? "Customer"),
                subtitle: Text("Order ID: ${widget.order.id}"),
              ),
            ),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Confirm delivery",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 12),
                    if (_uploadError != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          _uploadError!,
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    TextField(
                      controller: _otpController,
                      decoration: const InputDecoration(labelText: "OTP code"),
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: _submitting ? null : _pickPhoto,
                      icon: const Icon(Icons.camera_alt),
                      label: Text(
                        _photoPath == null ? "Add photo" : "Photo selected",
                      ),
                    ),
                    const SizedBox(height: 8),
                    OutlinedButton.icon(
                      onPressed: _submitting ? null : _pickSignature,
                      icon: const Icon(Icons.edit),
                      label: Text(
                        _signaturePath == null
                            ? "Capture signature"
                            : "Signature captured",
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _submitting ? null : _complete,
                child: Text(_submitting ? "Completing..." : "Complete delivery"),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
