import "dart:async";

import "package:flutter/material.dart";
import "package:flutter/services.dart";

class OrderRequestSheet extends StatefulWidget {
  const OrderRequestSheet({
    super.key,
    required this.onAccept,
    required this.onReject,
    required this.orderId,
    required this.vendorName,
    this.pickupDistance,
    this.payout,
    this.zone,
    this.expiresInSeconds = 20,
    this.onTimeout,
  });

  final VoidCallback onAccept;
  final VoidCallback onReject;
  final String orderId;
  final String vendorName;
  final String? pickupDistance;
  final String? payout;
  final String? zone;
  final int expiresInSeconds;
  final VoidCallback? onTimeout;

  @override
  State<OrderRequestSheet> createState() => _OrderRequestSheetState();
}

class _OrderRequestSheetState extends State<OrderRequestSheet> {
  Timer? _countdownTimer;
  Timer? _ringerTimer;
  int _remaining = 0;
  bool _handled = false;

  @override
  void initState() {
    super.initState();
    _remaining = widget.expiresInSeconds;
    _startCountdown();
    _startRinger();
  }

  @override
  void dispose() {
    _countdownTimer?.cancel();
    _ringerTimer?.cancel();
    super.dispose();
  }

  void _startCountdown() {
    _countdownTimer?.cancel();
    _countdownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remaining <= 1) {
        timer.cancel();
        _triggerTimeout();
      } else {
        setState(() => _remaining -= 1);
      }
    });
  }

  void _startRinger() {
    _ringerTimer?.cancel();
    _ringerTimer = Timer.periodic(const Duration(seconds: 4), (_) {
      if (!mounted) return;
      SystemSound.play(SystemSoundType.alert);
      HapticFeedback.mediumImpact();
    });
    SystemSound.play(SystemSoundType.alert);
    HapticFeedback.mediumImpact();
  }

  void _triggerTimeout() {
    if (_handled) return;
    _handled = true;
    if (widget.onTimeout != null) {
      widget.onTimeout!();
    } else {
      widget.onReject();
    }
  }

  void _handleAccept() {
    if (_handled) return;
    _handled = true;
    widget.onAccept();
  }

  void _handleReject() {
    if (_handled) return;
    _handled = true;
    widget.onReject();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: 16 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.outline,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
          const SizedBox(height: 12),
          const Text(
            "New order request",
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          Text("Vendor: ${widget.vendorName}"),
          Text("Order ID: ${widget.orderId}"),
          Text("Pickup: ${widget.pickupDistance ?? "-"}"),
          Text("Payout: ${widget.payout ?? "-"}"),
          if (widget.zone != null && widget.zone!.isNotEmpty)
            Text("Zone: ${widget.zone}"),
          const SizedBox(height: 8),
          Text("Respond in $_remaining s"),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: _handleReject,
                  child: const Text("Reject"),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  onPressed: _handleAccept,
                  child: const Text("Accept"),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
