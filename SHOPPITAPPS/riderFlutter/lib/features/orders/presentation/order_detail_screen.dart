import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../../../core/utils/app_launcher.dart";
import "../data/order_service.dart";
import "../models/order_models.dart";

class OrderDetailScreen extends StatefulWidget {
  const OrderDetailScreen({super.key, required this.order});

  final DriverOrder order;

  @override
  State<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends State<OrderDetailScreen> {
  bool _busy = false;

  Future<void> _accept() async {
    setState(() => _busy = true);
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.acceptOrder(widget.order.id);
    if (!mounted) return;
    setState(() => _busy = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result.success ? "Order accepted" : result.message)),
    );
  }

  Future<void> _reject() async {
    setState(() => _busy = true);
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    final result = await service.rejectOrder(widget.order.id);
    if (!mounted) return;
    setState(() => _busy = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(result.success ? "Order rejected" : result.message)),
    );
  }

  Future<void> _navigateToDelivery() async {
    final lat = widget.order.deliveryLatitude;
    final lng = widget.order.deliveryLongitude;
    if (lat == null || lng == null) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Delivery location not available.")),
      );
      return;
    }

    final launched = await AppLauncher.openMaps(
      latitude: lat,
      longitude: lng,
      label: widget.order.receiverName ?? "Customer",
    );

    if (!mounted) return;
    if (!launched) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Unable to open maps.")),
      );
    }
  }

  Future<void> _callCustomer() async {
    final phone = widget.order.receiverPhone;
    if (phone == null || phone.trim().isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("No phone number available.")),
      );
      return;
    }

    final launched = await AppLauncher.callPhone(phone);
    if (!mounted) return;
    if (!launched) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Unable to start call.")),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final order = widget.order;
    return Scaffold(
      appBar: AppBar(title: const Text("Order details")),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            order.vendor.businessName,
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        _statusPill(order.status),
                      ],
                    ),
                    const SizedBox(height: 8),
                    _detailRow("Order ID", order.id),
                  ],
                ),
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
                      "Delivery details",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 12),
                    if (order.receiverName != null)
                      _detailRow("Receiver", order.receiverName!),
                    if (order.receiverPhone != null)
                      _detailRow("Phone", order.receiverPhone!),
                    if (order.orderNotes != null &&
                        order.orderNotes!.trim().isNotEmpty)
                      _detailRow("Delivery instructions", order.orderNotes!),
                    if (order.netTotal != null)
                      _detailRow(
                        "Total",
                        "${order.netTotal!.amount} ${order.netTotal!.currency}",
                      ),
                    if (order.otpCode != null)
                      _detailRow("OTP", order.otpCode!),
                    if (order.deliveryLatitude != null &&
                        order.deliveryLongitude != null)
                      _detailRow(
                        "Location",
                        "${order.deliveryLatitude}, ${order.deliveryLongitude}",
                      ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      children: [
                        OutlinedButton.icon(
                          onPressed: _callCustomer,
                          icon: const Icon(Icons.call),
                          label: const Text("Call"),
                        ),
                        OutlinedButton.icon(
                          onPressed: _navigateToDelivery,
                          icon: const Icon(Icons.navigation),
                          label: const Text("Navigate"),
                        ),
                      ],
                    ),
                  ],
                ),
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
                      "Timeline",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 12),
                    _buildTimeline(order.status),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: _busy ? null : _reject,
                        child: const Text("Reject"),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: FilledButton(
                        onPressed: _busy ? null : _accept,
                        child: const Text("Accept"),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _detailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }

  Widget _buildTimeline(String status) {
    final steps = [
      "Order assigned",
      "Picked up",
      "Delivered",
    ];
    final activeStep = _statusStep(status);

    return Column(
      children: List.generate(steps.length, (index) {
        final isComplete = index <= activeStep;
        return Padding(
          padding: EdgeInsets.only(bottom: index == steps.length - 1 ? 0 : 10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                isComplete ? Icons.check_circle : Icons.radio_button_unchecked,
                size: 18,
                color: isComplete
                    ? Theme.of(context).colorScheme.primary
                    : Theme.of(context).colorScheme.outline,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  steps[index],
                  style: TextStyle(
                    fontWeight: isComplete ? FontWeight.w600 : FontWeight.w400,
                  ),
                ),
              ),
            ],
          ),
        );
      }),
    );
  }

  int _statusStep(String status) {
    final normalized = status.toLowerCase();
    if (normalized.contains("deliver")) {
      return 2;
    }
    if (normalized.contains("picked") ||
        normalized.contains("pickup") ||
        normalized.contains("accepted") ||
        normalized.contains("active")) {
      return 1;
    }
    return 0;
  }

  Widget _statusPill(String status) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: Theme.of(context).colorScheme.primary,
        ),
      ),
    );
  }
}
