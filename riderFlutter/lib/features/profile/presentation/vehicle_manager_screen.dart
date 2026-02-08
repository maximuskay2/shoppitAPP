import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/vehicle_service.dart";
import "../models/driver_vehicle.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kErrorColor = Color(0xFFE53935);

class VehicleManagerScreen extends StatefulWidget {
  const VehicleManagerScreen({super.key});

  @override
  State<VehicleManagerScreen> createState() => _VehicleManagerScreenState();
}

class _VehicleManagerScreenState extends State<VehicleManagerScreen> {
  List<DriverVehicle> _vehicles = [];
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadVehicles();
  }

  // -------------------------------------------------------------------------
  // LOGIC (KEPT INTACT)
  // -------------------------------------------------------------------------
  Future<void> _loadVehicles() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = VehicleService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchVehicles();
      if (!mounted) return;

      if (result.success) {
        _vehicles = result.data ?? [];
      } else {
        _error = result.message.isEmpty ? "Failed to load vehicles." : result.message;
      }
    } catch (_) {
      _error = "Failed to load vehicles.";
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  void _openVehicleForm([DriverVehicle? vehicle]) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent, // Important for rounded sheet
      builder: (_) => _VehicleFormSheet(
        vehicle: vehicle,
        onSaved: _loadVehicles,
      ),
    );
  }

  Future<void> _deleteVehicle(DriverVehicle vehicle) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Delete Vehicle?"),
        content: Text("Are you sure you want to remove ${vehicle.vehicleType}?"),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text("Cancel", style: TextStyle(color: kTextLight)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.of(context).pop(true),
            style: ElevatedButton.styleFrom(
              backgroundColor: kErrorColor,
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text("Delete"),
          ),
        ],
      ),
    );

    if (shouldDelete != true) return;

    final service = VehicleService(apiClient: AppScope.of(context).apiClient);
    final result = await service.deleteVehicle(vehicle.id);
    if (!mounted) return;

    if (result.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Vehicle deleted successfully")),
      );
      _loadVehicles();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message.isEmpty ? "Delete failed" : result.message)),
      );
    }
  }

  Future<void> _setActive(DriverVehicle vehicle) async {
    final service = VehicleService(apiClient: AppScope.of(context).apiClient);
    final result = await service.updateVehicle(
      id: vehicle.id,
      isActive: true,
    );
    if (!mounted) return;

    if (result.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Active vehicle updated")),
      );
      _loadVehicles();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message.isEmpty ? "Update failed" : result.message)),
      );
    }
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
        appBar: AppBar(title: const Text("Garage"), elevation: 0, backgroundColor: kBackgroundColor),
        body: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.error_outline, size: 48, color: kErrorColor.withOpacity(0.5)),
              const SizedBox(height: 16),
              Text(_error!, style: const TextStyle(color: kTextLight)),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _loadVehicles,
                style: FilledButton.styleFrom(backgroundColor: kPrimaryColor),
                child: const Text("Try Again"),
              ),
            ],
          ),
        ),
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
          "My Garage",
          style: TextStyle(color: kTextDark, fontWeight: FontWeight.w800),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          physics: const BouncingScrollPhysics(),
          children: [
            if (_vehicles.isEmpty)
              _buildEmptyState()
            else
              ..._vehicles.map(_build3DVehicleCard),
            
            const SizedBox(height: 80), // Space for FAB
          ],
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openVehicleForm(),
        backgroundColor: kPrimaryColor,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text("Add Vehicle", style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const SizedBox(height: 60),
          Icon(Icons.garage_rounded, size: 80, color: kTextLight.withOpacity(0.3)),
          const SizedBox(height: 16),
          const Text(
            "Your Garage is Empty",
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
          ),
          const SizedBox(height: 8),
          const Text(
            "Add a vehicle to start delivering.",
            style: TextStyle(color: kTextLight),
          ),
        ],
      ),
    );
  }

  Widget _build3DVehicleCard(DriverVehicle vehicle) {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        border: vehicle.isActive ? Border.all(color: kPrimaryColor, width: 2) : null,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2C3E50).withOpacity(0.08),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header: Icon + Type + Active Badge
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: vehicle.isActive ? kPrimaryColor.withOpacity(0.1) : kBackgroundColor,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    _getVehicleIcon(vehicle.vehicleType),
                    color: vehicle.isActive ? kPrimaryColor : kTextLight,
                    size: 24,
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        vehicle.vehicleType,
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: kTextDark),
                      ),
                      if (vehicle.isActive)
                        const Text(
                          "Currently Active",
                          style: TextStyle(fontSize: 12, color: kPrimaryColor, fontWeight: FontWeight.w600),
                        ),
                    ],
                  ),
                ),
                if (!vehicle.isActive)
                  TextButton(
                    onPressed: () => _setActive(vehicle),
                    child: const Text("Select", style: TextStyle(fontWeight: FontWeight.bold)),
                  )
                else 
                  const Icon(Icons.check_circle, color: kPrimaryColor),
              ],
            ),
          ),
          
          const Divider(height: 1, color: kBackgroundColor),
          
          // Details Grid
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                _buildDetailItem("Plate", vehicle.plateNumber),
                _buildDetailItem("Model", vehicle.model),
                _buildDetailItem("Color", vehicle.color),
              ],
            ),
          ),

          // Actions Footer
          Container(
            decoration: BoxDecoration(
              color: kBackgroundColor.withOpacity(0.5),
              borderRadius: const BorderRadius.vertical(bottom: Radius.circular(20)),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                IconButton(
                  onPressed: () => _openVehicleForm(vehicle),
                  icon: const Icon(Icons.edit_outlined, size: 20, color: kTextLight),
                  tooltip: "Edit",
                ),
                IconButton(
                  onPressed: () => _deleteVehicle(vehicle),
                  icon: const Icon(Icons.delete_outline_rounded, size: 20, color: kErrorColor),
                  tooltip: "Delete",
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailItem(String label, String? value) {
    return Expanded(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(), style: const TextStyle(fontSize: 10, color: kTextLight, fontWeight: FontWeight.w600)),
          const SizedBox(height: 4),
          Text(
            value != null && value.isNotEmpty ? value : "-",
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: kTextDark),
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  IconData _getVehicleIcon(String type) {
    final t = type.toLowerCase();
    if (t.contains("bike") || t.contains("motor")) return Icons.two_wheeler;
    if (t.contains("car") || t.contains("sedan")) return Icons.directions_car;
    if (t.contains("truck") || t.contains("van")) return Icons.local_shipping;
    return Icons.directions_car_filled;
  }
}

// ---------------------------------------------------------------------------
// FORM SHEET (Redesigned)
// ---------------------------------------------------------------------------
class _VehicleFormSheet extends StatefulWidget {
  const _VehicleFormSheet({
    this.vehicle,
    required this.onSaved,
  });

  final DriverVehicle? vehicle;
  final VoidCallback onSaved;

  @override
  State<_VehicleFormSheet> createState() => _VehicleFormSheetState();
}

class _VehicleFormSheetState extends State<_VehicleFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _typeController;
  late final TextEditingController _licenseController;
  late final TextEditingController _plateController;
  late final TextEditingController _modelController;
  late final TextEditingController _colorController;
  bool _saving = false;
  bool _isActive = false;

  @override
  void initState() {
    super.initState();
    _typeController = TextEditingController(text: widget.vehicle?.vehicleType ?? "");
    _licenseController = TextEditingController(text: widget.vehicle?.licenseNumber ?? "");
    _plateController = TextEditingController(text: widget.vehicle?.plateNumber ?? "");
    _modelController = TextEditingController(text: widget.vehicle?.model ?? "");
    _colorController = TextEditingController(text: widget.vehicle?.color ?? "");
    _isActive = widget.vehicle?.isActive ?? false;
  }

  @override
  void dispose() {
    _typeController.dispose();
    _licenseController.dispose();
    _plateController.dispose();
    _modelController.dispose();
    _colorController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);
    final service = VehicleService(apiClient: AppScope.of(context).apiClient);

    final result = widget.vehicle == null
        ? await service.createVehicle(
            vehicleType: _typeController.text.trim(),
            licenseNumber: _licenseController.text.trim().isEmpty ? null : _licenseController.text.trim(),
            plateNumber: _plateController.text.trim().isEmpty ? null : _plateController.text.trim(),
            model: _modelController.text.trim().isEmpty ? null : _modelController.text.trim(),
            color: _colorController.text.trim().isEmpty ? null : _colorController.text.trim(),
            isActive: _isActive,
          )
        : await service.updateVehicle(
            id: widget.vehicle!.id,
            vehicleType: _typeController.text.trim(),
            licenseNumber: _licenseController.text.trim().isEmpty ? null : _licenseController.text.trim(),
            plateNumber: _plateController.text.trim().isEmpty ? null : _plateController.text.trim(),
            model: _modelController.text.trim().isEmpty ? null : _modelController.text.trim(),
            color: _colorController.text.trim().isEmpty ? null : _colorController.text.trim(),
            isActive: _isActive,
          );

    if (!mounted) return;

    if (result.success) {
      widget.onSaved();
      Navigator.of(context).pop();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.message.isEmpty ? "Save failed" : result.message)),
      );
      setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: 24 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Form(
        key: _formKey,
        child: ListView(
          shrinkWrap: true,
          physics: const BouncingScrollPhysics(),
          children: [
            Center(
              child: Container(
                width: 40, height: 4,
                decoration: BoxDecoration(color: kTextLight.withOpacity(0.3), borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 20),
            Text(
              widget.vehicle == null ? "Add New Vehicle" : "Edit Vehicle",
              style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: kTextDark),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            _buildInput(_typeController, "Vehicle Type (e.g. Bike)", Icons.motorcycle_rounded, true),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(child: _buildInput(_plateController, "Plate Number", Icons.tag)),
                const SizedBox(width: 16),
                Expanded(child: _buildInput(_colorController, "Color", Icons.color_lens_outlined)),
              ],
            ),
            const SizedBox(height: 16),
            _buildInput(_modelController, "Model / Make", Icons.directions_car_outlined),
            const SizedBox(height: 16),
            _buildInput(_licenseController, "License Number", Icons.badge_outlined),
            const SizedBox(height: 24),
            
            // Custom Switch Tile
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: kBackgroundColor,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text("Set as Active Vehicle", style: TextStyle(fontWeight: FontWeight.w600)),
                  Switch(
                    value: _isActive,
                    activeColor: kPrimaryColor,
                    onChanged: (value) => setState(() => _isActive = value),
                  ),
                ],
              ),
            ),
            
            const SizedBox(height: 32),
            SizedBox(
              width: double.infinity,
              height: 56,
              child: ElevatedButton(
                onPressed: _saving ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: kPrimaryColor,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                ),
                child: _saving 
                  ? const SizedBox(height: 24, width: 24, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                  : const Text("Save Vehicle", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInput(TextEditingController controller, String label, IconData icon, [bool required = false]) {
    return Container(
      decoration: BoxDecoration(
        color: kBackgroundColor,
        borderRadius: BorderRadius.circular(16),
      ),
      child: TextFormField(
        controller: controller,
        style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
        validator: required ? (val) => val == null || val.isEmpty ? "Required" : null : null,
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(color: kTextLight),
          prefixIcon: Icon(icon, color: kTextLight),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        ),
      ),
    );
  }
}