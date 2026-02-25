import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../../../core/utils/app_launcher.dart";
import "../../../core/widgets/app_alert.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";
import "active_delivery_screen.dart";

class ActivePickupScreen extends StatefulWidget {
  const ActivePickupScreen({
    super.key,
    required this.order,
  });

  final DriverOrder order;

  @override
  State<ActivePickupScreen> createState() => _ActivePickupScreenState();
}

class _ActivePickupScreenState extends State<ActivePickupScreen> {
  late Map<String, bool> _checkedItems;

  @override
  void initState() {
    super.initState();
    _checkedItems = {
      for (final item in widget.order.lineItems) item.id: false,
    };
  }

  Future<void> _confirmPickup(BuildContext context) async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.pickupOrder(widget.order.id);
    if (!context.mounted) return;

    if (result.success) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ActiveDeliveryScreen(
            order: widget.order,
          ),
        ),
      );
    } else {
      AppAlert.show(
        context,
        message: result.message.isEmpty ? "Pickup failed." : result.message,
        type: AppAlertType.error,
      );
    }
  }

  Future<void> _navigateToVendor(BuildContext context) async {
    final lat = widget.order.vendor.latitude;
    final lng = widget.order.vendor.longitude;
    if (lat == null || lng == null) {
      AppAlert.show(
        context,
        message: "Vendor location not available.",
        type: AppAlertType.warning,
      );
      return;
    }

    final launched = await AppLauncher.openMaps(
      latitude: lat,
      longitude: lng,
      label: widget.order.vendor.businessName,
    );

    if (!context.mounted) return;
    if (!launched) {
      AppAlert.show(
        context,
        message: "Unable to open maps.",
        type: AppAlertType.error,
      );
    }
  }

  Future<void> _cancelOrder(BuildContext context) async {
    final reason = await _promptCancelReason(context);
    if (reason == null) return;

    final shouldCancel = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Cancel order?"),
        content: const Text(
          "This will cancel the delivery assignment. A penalty may apply.",
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text("No"),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text("Yes, cancel"),
          ),
        ],
      ),
    );

    if (shouldCancel != true) return;
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.cancelOrder(
      widget.order.id,
      reason: reason,
    );
    if (!context.mounted) return;

    if (result.success) {
      AppAlert.show(
        context,
        message: "Order cancelled",
        type: AppAlertType.success,
      );
      Navigator.of(context).pop();
      return;
    }

    AppAlert.show(
      context,
      message: result.message.isEmpty ? "Cancel failed." : result.message,
      type: AppAlertType.error,
    );
  }

  Future<String?> _promptCancelReason(BuildContext context) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (dialogContext) {
        return StatefulBuilder(
          builder: (context, setState) {
            final reason = controller.text.trim();
            final canSubmit = reason.length >= 3;

            return AlertDialog(
              title: const Text("Cancellation reason"),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Text(
                    "Please provide a brief reason for cancelling this order.",
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: controller,
                    maxLength: 255,
                    minLines: 2,
                    maxLines: 4,
                    decoration: const InputDecoration(
                      labelText: "Reason",
                      hintText: "e.g., vendor closed, customer unreachable",
                    ),
                    onChanged: (_) => setState(() {}),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    "Penalties may apply for late cancellations.",
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(dialogContext).pop(null),
                  child: const Text("Close"),
                ),
                FilledButton(
                  onPressed: canSubmit
                      ? () => Navigator.of(dialogContext).pop(reason)
                      : null,
                  child: const Text("Continue"),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Pickup")),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: ListTile(
                title: Text(widget.order.vendor.businessName),
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
                      "Pickup checklist",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 12),
                    if (widget.order.lineItems.isEmpty)
                      ..._defaultChecklist()
                    else
                      ...widget.order.lineItems.map(_buildItemChecklist),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => _confirmPickup(context),
                child: const Text("Confirm pickup"),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _navigateToVendor(context),
                icon: const Icon(Icons.navigation),
                label: const Text("Navigate to vendor"),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton(
                onPressed: () => _cancelOrder(context),
                child: const Text("Cancel order"),
              ),
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _defaultChecklist() {
    const items = [
      "Verify items and packaging",
      "Confirm order code",
      "Collect receipt if needed",
    ];
    return items.map((label) => _checkItem(label)).toList();
  }

  Widget _buildItemChecklist(OrderLineItem item) {
    final checked = _checkedItems[item.id] ?? false;
    final label = "${item.quantity} x ${item.name}";

    return CheckboxListTile(
      contentPadding: EdgeInsets.zero,
      value: checked,
      title: Text(label),
      onChanged: (value) {
        setState(() {
          _checkedItems[item.id] = value ?? false;
        });
      },
    );
  }

  Widget _checkItem(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          const Icon(Icons.check_circle_outline, size: 18),
          const SizedBox(width: 8),
          Expanded(child: Text(label)),
        ],
      ),
    );
  }
}
