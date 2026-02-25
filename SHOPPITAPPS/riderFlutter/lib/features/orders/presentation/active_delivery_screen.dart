import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../../../core/utils/app_launcher.dart";
import "../../../core/widgets/app_alert.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";
import "proof_of_delivery_screen.dart";

class ActiveDeliveryScreen extends StatelessWidget {
  const ActiveDeliveryScreen({
    super.key,
    required this.order,
  });

  final DriverOrder order;

  Future<void> _startDelivery(BuildContext context) async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.startDelivery(order.id);
    if (!context.mounted) return;

    if (result.success) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ProofOfDeliveryScreen(
            order: order,
          ),
        ),
      );
    } else {
      AppAlert.show(
        context,
        message:
            result.message.isEmpty ? "Failed to start delivery." : result.message,
        type: AppAlertType.error,
      );
    }
  }

  Future<void> _navigateToCustomer(BuildContext context) async {
    final lat = order.deliveryLatitude;
    final lng = order.deliveryLongitude;
    if (lat == null || lng == null) {
      AppAlert.show(
        context,
        message: "Customer location not available.",
        type: AppAlertType.warning,
      );
      return;
    }

    final launched = await AppLauncher.openMaps(
      latitude: lat,
      longitude: lng,
      label: order.receiverName ?? "Customer",
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

  Future<void> _callCustomer(BuildContext context) async {
    final phone = order.receiverPhone;
    if (phone == null || phone.trim().isEmpty) {
      AppAlert.show(
        context,
        message: "No phone number available.",
        type: AppAlertType.warning,
      );
      return;
    }

    final launched = await AppLauncher.callPhone(phone);
    if (!context.mounted) return;
    if (!launched) {
      AppAlert.show(
        context,
        message: "Unable to start call.",
        type: AppAlertType.error,
      );
    }
  }

  Future<void> _chatCustomer(BuildContext context) async {
    final phone = order.receiverPhone;
    if (phone == null || phone.trim().isEmpty) {
      AppAlert.show(
        context,
        message: "No phone number available.",
        type: AppAlertType.warning,
      );
      return;
    }

    final launched = await AppLauncher.openWhatsApp(phone);
    if (!context.mounted) return;
    if (!launched) {
      AppAlert.show(
        context,
        message: "Unable to open WhatsApp.",
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
      order.id,
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
                      hintText: "e.g., customer unreachable, safety concerns",
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
      appBar: AppBar(title: const Text("Delivery")),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: ListTile(
                title: Text(order.receiverName ?? "Customer"),
                subtitle: Text("Order ID: ${order.id}"),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      icon: const Icon(Icons.chat_bubble_outline),
                      onPressed: () => _chatCustomer(context),
                    ),
                    IconButton(
                      icon: const Icon(Icons.call),
                      onPressed: () => _callCustomer(context),
                    ),
                  ],
                ),
              ),
            ),
            if (order.receiverPhone != null && order.receiverPhone!.isNotEmpty)
              Padding(
                padding: const EdgeInsets.only(top: 8),
                child: Text("Phone: ${order.receiverPhone}"),
              ),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      "Delivery steps",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 12),
                    _stepItem("Navigate to customer"),
                    _stepItem("Confirm address"),
                    _stepItem("Collect proof of delivery"),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => _startDelivery(context),
                child: const Text("Arrived at customer"),
              ),
            ),
            const SizedBox(height: 8),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _navigateToCustomer(context),
                icon: const Icon(Icons.navigation),
                label: const Text("Navigate to customer"),
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

  Widget _stepItem(String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          const Icon(Icons.radio_button_unchecked, size: 18),
          const SizedBox(width: 8),
          Expanded(child: Text(label)),
        ],
      ),
    );
  }
}
