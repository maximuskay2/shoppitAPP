import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class SOSButton extends StatefulWidget {
  final String? orderId;
  final VoidCallback? onSOSTriggered;

  const SOSButton({
    Key? key,
    this.orderId,
    this.onSOSTriggered,
  }) : super(key: key);

  @override
  State<SOSButton> createState() => _SOSButtonState();
}

class _SOSButtonState extends State<SOSButton> with SingleTickerProviderStateMixin {
  late AnimationController _animationController;
  bool _isHolding = false;
  bool _sosTriggered = false;
  double _holdProgress = 0.0;

  // Emergency contacts - these should come from settings/API
  static const String _emergencyNumber = '112'; // Local emergency number
  static const String _supportNumber = '+234XXXXXXXXXX'; // Company support

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 3),
    );

    _animationController.addListener(() {
      setState(() {
        _holdProgress = _animationController.value;
      });

      if (_animationController.isCompleted && !_sosTriggered) {
        _triggerSOS();
      }
    });
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  void _onTapDown(TapDownDetails details) {
    setState(() {
      _isHolding = true;
    });
    _animationController.forward(from: 0);
    HapticFeedback.heavyImpact();
  }

  void _onTapUp(TapUpDetails details) {
    if (!_sosTriggered) {
      _animationController.stop();
      _animationController.reset();
      setState(() {
        _isHolding = false;
        _holdProgress = 0.0;
      });
    }
  }

  void _onTapCancel() {
    if (!_sosTriggered) {
      _animationController.stop();
      _animationController.reset();
      setState(() {
        _isHolding = false;
        _holdProgress = 0.0;
      });
    }
  }

  Future<void> _triggerSOS() async {
    setState(() {
      _sosTriggered = true;
    });

    HapticFeedback.vibrate();

    // Get current location
    Position? position;
    try {
      position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
        timeLimit: const Duration(seconds: 5),
      );
    } catch (e) {
      print('Failed to get location: $e');
    }

    // Show options dialog
    if (mounted) {
      _showSOSDialog(position);
    }

    // Notify callback
    widget.onSOSTriggered?.call();

    // Send SOS to backend
    _sendSOSToBackend(position);
  }

  Future<void> _sendSOSToBackend(Position? position) async {
    try {
      await ApiService().sendSOS(
        orderId: widget.orderId,
        latitude: position?.latitude,
        longitude: position?.longitude,
      );
    } catch (e) {
      print('Failed to send SOS to backend: $e');
    }
  }

  void _showSOSDialog(Position? position) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.red[100],
                shape: BoxShape.circle,
              ),
              child: Icon(Icons.warning_rounded, color: Colors.red[700], size: 24),
            ),
            const SizedBox(width: 12),
            const Text('Emergency SOS'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Your emergency has been registered. What would you like to do?',
              style: TextStyle(fontSize: 14),
            ),
            if (position != null) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.grey[100],
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    Icon(Icons.location_on, size: 16, color: Colors.grey[600]),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        'Location: ${position.latitude.toStringAsFixed(6)}, ${position.longitude.toStringAsFixed(6)}',
                        style: TextStyle(fontSize: 11, color: Colors.grey[600]),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          // Call Emergency
          ElevatedButton.icon(
            onPressed: () => _callNumber(_emergencyNumber),
            icon: const Icon(Icons.call, size: 18),
            label: const Text('Call Emergency'),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
          ),
          // Call Support
          OutlinedButton.icon(
            onPressed: () => _callNumber(_supportNumber),
            icon: const Icon(Icons.headset_mic, size: 18),
            label: const Text('Call Support'),
          ),
          // Share Location
          if (position != null)
            TextButton.icon(
              onPressed: () => _shareLocation(position),
              icon: const Icon(Icons.share, size: 18),
              label: const Text('Share Location'),
            ),
          // Close
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              setState(() {
                _sosTriggered = false;
                _isHolding = false;
                _holdProgress = 0.0;
              });
            },
            child: const Text('I\'m Safe'),
          ),
        ],
        actionsAlignment: MainAxisAlignment.center,
        actionsPadding: const EdgeInsets.all(16),
      ),
    );
  }

  Future<void> _callNumber(String number) async {
    final uri = Uri.parse('tel:$number');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _shareLocation(Position position) async {
    final googleMapsUrl = 'https://www.google.com/maps?q=${position.latitude},${position.longitude}';
    final uri = Uri.parse('sms:?body=EMERGENCY! I need help. My location: $googleMapsUrl');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: _sosTriggered ? null : _onTapDown,
      onTapUp: _sosTriggered ? null : _onTapUp,
      onTapCancel: _sosTriggered ? null : _onTapCancel,
      child: Container(
        width: 70,
        height: 70,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: _sosTriggered ? Colors.red : Colors.red[600],
          boxShadow: [
            BoxShadow(
              color: Colors.red.withOpacity(0.4),
              blurRadius: _isHolding ? 20 : 10,
              spreadRadius: _isHolding ? 5 : 2,
            ),
          ],
        ),
        child: Stack(
          alignment: Alignment.center,
          children: [
            // Progress indicator
            if (_isHolding && !_sosTriggered)
              SizedBox(
                width: 70,
                height: 70,
                child: CircularProgressIndicator(
                  value: _holdProgress,
                  strokeWidth: 4,
                  backgroundColor: Colors.red[300],
                  valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              ),
            // SOS Text
            Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  _sosTriggered ? Icons.check : Icons.warning_rounded,
                  color: Colors.white,
                  size: 24,
                ),
                Text(
                  _sosTriggered ? 'SENT' : 'SOS',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

// Floating SOS Button for use in delivery screens
class FloatingSOSButton extends StatelessWidget {
  final String? orderId;

  const FloatingSOSButton({Key? key, this.orderId}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Positioned(
      right: 16,
      bottom: 100,
      child: SOSButton(orderId: orderId),
    );
  }
}
