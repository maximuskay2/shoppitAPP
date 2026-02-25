import "dart:async";

import "package:flutter/material.dart";
import "package:geolocator/geolocator.dart";
import "package:google_maps_flutter/google_maps_flutter.dart";
import "../../../core/network/remote_config_service.dart";

import "../../../app/app_scope.dart";
import "../../../core/location/location_tracker.dart";
import "../../orders/data/order_service.dart";
import "../../orders/models/order_models.dart";
import "../../orders/presentation/order_request_sheet.dart";
import "../../orders/presentation/active_pickup_screen.dart";
import "../../orders/presentation/active_delivery_screen.dart";
import "../../profile/data/profile_service.dart";
import "../../earnings/data/earnings_service.dart";
import "../../earnings/models/earnings_models.dart";
import "../../../core/network/api_paths.dart";
import "../data/navigation_service.dart";
import "../models/route_models.dart";
import "route_optimization_screen.dart";

class HomeMapScreen extends StatefulWidget {
  const HomeMapScreen({super.key});

  @override
  State<HomeMapScreen> createState() => _HomeMapScreenState();
}

class _HomeMapScreenState extends State<HomeMapScreen>
    with WidgetsBindingObserver {
  bool _online = false;
  bool _saving = false;
  final LatLng _fallbackOrigin = const LatLng(6.5244, 3.3792);
  LatLng? _currentLocation;
  LatLng? _destination;
  DriverOrder? _activeOrder;
  List<DriverOrder> _availableOrders = [];
  Set<Polyline> _polylines = {};
  Set<Circle> _heatmapCircles = {};
  String? _routeMessage;
  bool _loadingOrder = false;
  String? _lastOrderStatus;
  String? _lastOrderId;
  String? _lastRequestOrderId;
  int _orderRequestIndex = 0;
  bool _hasLoadedOnce = false;
  bool _isForeground = true;
  bool _requestOpen = false;
  EarningsSummary? _earningsSummary;
  DriverStats? _driverStats;
  bool _loadingSummary = false;

  LocationTracker? _tracker;
  StreamSubscription<Position>? _positionSubscription;
  DateTime? _lastRouteUpdate;
  LatLng? _lastRouteOrigin;
  Timer? _orderRefreshTimer;
  Timer? _availableRefreshTimer;
  DateTime? _lastHeatmapUpdate;
  LatLng? _lastHeatmapOrigin;

  GoogleMapController? _mapController;
  bool _hasAnimatedToDriver = false;

  String? _googleMapsApiKey;
  List<String> _fcmTokens = [];
  bool _initRun = false;
  bool _cardMinimized = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_initRun) {
      _initRun = true;
      _loadRemoteConfig();
      _loadActiveOrder();
      _loadAvailableOrders();
      _loadTodaySummary();
      _startLiveLocation();
      _orderRefreshTimer = Timer.periodic(
        const Duration(seconds: 20),
        (_) => _loadActiveOrder(),
      );
      _availableRefreshTimer = Timer.periodic(
        const Duration(seconds: 30),
        (_) => _loadAvailableOrders(),
      );
    }
  }

  Future<void> _loadRemoteConfig() async {
    final apiClient = AppScope.of(context).apiClient;
    final remoteConfig = RemoteConfigService(apiClient.dio);
    final key = await remoteConfig.fetchGoogleMapsApiKey();
    final tokens = await remoteConfig.fetchFcmTokens();
    setState(() {
      _googleMapsApiKey = key;
      _fcmTokens = tokens;
    });
  }

  @override
  void dispose() {
    _positionSubscription?.cancel();
    _orderRefreshTimer?.cancel();
    _availableRefreshTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    _isForeground = state == AppLifecycleState.resumed;
  }

  Future<void> _toggleOnline(bool value) async {
    setState(() {
      _online = value;
      _saving = true;
    });

    final service = ProfileService(apiClient: AppScope.of(context).apiClient);
    try {
      await service.updateStatus(isOnline: value);
      _tracker ??= LocationTracker(apiClient: AppScope.of(context).apiClient);
      if (value) {
        await _tracker?.startTracking();
      } else {
        await _tracker?.stopTracking();
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _online = !value);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Unable to update status.")),
      );
    } finally {
      if (!mounted) return;
      setState(() => _saving = false);
    }
  }

  Future<void> _loadActiveOrder() async {
    if (_loadingOrder) return;
    setState(() => _loadingOrder = true);
    final service = OrderService(apiClient: AppScope.of(context).apiClient);

    try {
      final response = await service.fetchActiveOrders();
      if (!mounted) return;

      final order = (response.data ?? []).isNotEmpty ? response.data!.first : null;
      final destination = _resolveDestination(order);
      final nextStatus = order?.status;
      final statusChanged = nextStatus != null && nextStatus != _lastOrderStatus;
      final nextOrderId = order?.id;
      final isNewOrder = _hasLoadedOnce &&
          nextOrderId != null &&
          nextOrderId != _lastOrderId;

      setState(() {
        _activeOrder = order;
        _destination = destination;
        _lastOrderStatus = nextStatus;
        _lastOrderId = nextOrderId;
        _loadingOrder = false;
      });

      _hasLoadedOnce = true;

      if (isNewOrder && order != null && _isForeground && _online) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (!mounted) return;
          _openActiveOrder(order);
        });
      }

      if (destination != null) {
        if (statusChanged) {
          _loadRoute();
        }
      } else {
        setState(() {
          _polylines = {};
          _routeMessage = "No active delivery route.";
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _loadingOrder = false;
        _routeMessage = "Failed to load active order.";
      });
    }
  }

  Future<void> _startLiveLocation() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return;

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }

    if (permission != LocationPermission.always &&
        permission != LocationPermission.whileInUse) {
      return;
    }

    // Get initial position immediately so map loads with driver location
    Position? initialPosition = await Geolocator.getLastKnownPosition();
    if (initialPosition == null) {
      try {
        initialPosition = await Geolocator.getCurrentPosition(
          locationSettings: const LocationSettings(
            accuracy: LocationAccuracy.high,
          ),
        );
      } catch (_) {
        // Fall through; stream may provide position later
      }
    }
    if (initialPosition != null && mounted) {
      final next = LatLng(initialPosition.latitude, initialPosition.longitude);
      setState(() => _currentLocation = next);
      _moveCameraToDriver(next);
    }

    _positionSubscription?.cancel();
    const settings = LocationSettings(
      accuracy: LocationAccuracy.high,
      distanceFilter: 15,
    );

    _positionSubscription = Geolocator.getPositionStream(
      locationSettings: settings,
    ).listen((position) {
      final next = LatLng(position.latitude, position.longitude);
      setState(() => _currentLocation = next);
      _moveCameraToDriver(next);
      _refreshRouteIfNeeded(next);
      _refreshHeatmapIfNeeded(next);
    });
  }

  void _moveCameraToDriver(LatLng location) {
    if (_hasAnimatedToDriver) return;
    if (_mapController == null) return;
    _hasAnimatedToDriver = true;
    _mapController!.animateCamera(
      CameraUpdate.newCameraPosition(
        CameraPosition(target: location, zoom: 14),
      ),
      duration: const Duration(milliseconds: 500),
    );
  }

  Future<void> _loadAvailableOrders() async {
    final service = OrderService(apiClient: AppScope.of(context).apiClient);
    try {
      final response = await service.fetchAvailableOrders(
        latitude: _currentLocation?.latitude,
        longitude: _currentLocation?.longitude,
      );

      if (!mounted) return;

      final orders = response.data ?? [];
      setState(() {
        _availableOrders = orders;
        _heatmapCircles = _buildHeatmap(orders);
      });

      if (_lastRequestOrderId != null &&
          !_availableOrders.any((order) => order.id == _lastRequestOrderId)) {
        _lastRequestOrderId = null;
        _orderRequestIndex = 0;
      }

      _maybeShowOrderRequest();
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _availableOrders = [];
        _heatmapCircles = {};
      });
    }
  }

  Future<void> _loadTodaySummary() async {
    if (_loadingSummary) return;
    setState(() => _loadingSummary = true);

    final service = EarningsService(apiClient: AppScope.of(context).apiClient);
    try {
      final summaryResult = await service.fetchSummary();
      final statsResult = await service.fetchStats();
      if (!mounted) return;
      setState(() {
        _earningsSummary = summaryResult.data;
        _driverStats = statsResult.data;
      });
    } catch (_) {
      if (!mounted) return;
    } finally {
      if (!mounted) return;
      setState(() => _loadingSummary = false);
    }
  }

  Future<void> _loadRoute() async {
    if (_currentLocation == null || _destination == null) {
      setState(() => _routeMessage = "Waiting for route coordinates.");
      return;
    }

    final service = NavigationService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchRoute(
        originLat: _currentLocation!.latitude,
        originLng: _currentLocation!.longitude,
        destinationLat: _destination!.latitude,
        destinationLng: _destination!.longitude,
      );

      if (!mounted) return;

      if (result.success && result.data != null) {
        final route = result.data!;
        setState(() {
          _polylines = {
            Polyline(
              polylineId: const PolylineId("route"),
              color: Theme.of(context).colorScheme.primary,
              width: 5,
              points: route.polyline
                  .map((point) => LatLng(point.lat, point.lng))
                  .toList(),
            )
          };
          _routeMessage =
              "${route.distanceKm} km · ${route.etaMinutes} min";
        });
      } else {
        setState(() {
          _routeMessage = result.message.isEmpty
              ? "Unable to load route"
              : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _routeMessage = "Unable to load route");
    }
  }

  void _refreshRouteIfNeeded(LatLng current) {
    if (_destination == null) return;

    final now = DateTime.now();
    if (_lastRouteUpdate != null &&
        now.difference(_lastRouteUpdate!).inSeconds < 12) {
      return;
    }

    if (_lastRouteOrigin != null) {
      final distance = Geolocator.distanceBetween(
        _lastRouteOrigin!.latitude,
        _lastRouteOrigin!.longitude,
        current.latitude,
        current.longitude,
      );
      if (distance < 30) {
        return;
      }
    }

    _lastRouteUpdate = now;
    _lastRouteOrigin = current;
    _loadRoute();
  }

  void _refreshHeatmapIfNeeded(LatLng current) {
    final now = DateTime.now();
    if (_lastHeatmapUpdate != null &&
        now.difference(_lastHeatmapUpdate!).inSeconds < 20) {
      return;
    }

    if (_lastHeatmapOrigin != null) {
      final distance = Geolocator.distanceBetween(
        _lastHeatmapOrigin!.latitude,
        _lastHeatmapOrigin!.longitude,
        current.latitude,
        current.longitude,
      );
      if (distance < 80) {
        return;
      }
    }

    _lastHeatmapUpdate = now;
    _lastHeatmapOrigin = current;
    _loadAvailableOrders();
  }

  Set<Circle> _buildHeatmap(List<DriverOrder> orders) {
    final buckets = <String, List<LatLng>>{};

    for (final order in orders) {
      final lat = order.vendor.latitude;
      final lng = order.vendor.longitude;
      if (lat == null || lng == null) continue;

      final roundedLat = (lat * 100).roundToDouble() / 100;
      final roundedLng = (lng * 100).roundToDouble() / 100;
      final key = "$roundedLat,$roundedLng";
      buckets.putIfAbsent(key, () => []).add(LatLng(lat, lng));
    }

    return buckets.entries.map((entry) {
      final points = entry.value;
      final count = points.length;
      final avgLat = points.map((p) => p.latitude).reduce((a, b) => a + b) /
          points.length;
      final avgLng = points.map((p) => p.longitude).reduce((a, b) => a + b) /
          points.length;

      final opacity = (0.15 + (count * 0.08)).clamp(0.2, 0.6);
      final radius = 180 + (count * 40);

      return Circle(
        circleId: CircleId(entry.key),
        center: LatLng(avgLat, avgLng),
        radius: radius.toDouble(),
        fillColor: const Color(0xFFE4572E).withOpacity(opacity),
        strokeColor: const Color(0xFFB33C1B).withOpacity(opacity + 0.1),
        strokeWidth: 1,
      );
    }).toSet();
  }

  LatLng? _resolveDestination(DriverOrder? order) {
    if (order == null) return null;

    final isDeliveryStage = _isDeliveryStage(order.status);

    if (isDeliveryStage &&
        order.deliveryLatitude != null &&
        order.deliveryLongitude != null) {
      return LatLng(order.deliveryLatitude!, order.deliveryLongitude!);
    }

    if (order.vendor.latitude != null && order.vendor.longitude != null) {
      return LatLng(order.vendor.latitude!, order.vendor.longitude!);
    }

    return null;
  }

  bool _isDeliveryStage(String status) {
    final normalized = status.toLowerCase();
    return normalized.contains("picked") ||
        normalized.contains("delivery") ||
        normalized.contains("out_for_delivery") ||
        normalized.contains("out-for-delivery") ||
        normalized.contains("delivering") ||
        normalized.contains("delivered");
  }

  int _statusStep(String status) {
    final normalized = status.toLowerCase();
    if (normalized.contains("delivered")) {
      return 2;
    }
    if (normalized.contains("out_for_delivery") ||
        normalized.contains("out-for-delivery") ||
        normalized.contains("delivery") ||
        normalized.contains("picked")) {
      return 1;
    }
    return 0;
  }

  void _maybeShowOrderRequest() {
    if (!_online || !_isForeground || _requestOpen) return;
    if (_activeOrder != null || _availableOrders.isEmpty) return;

    final sortedOrders = _sortedAvailableOrders();

    if (sortedOrders.length == 1 &&
        sortedOrders.first.id == _lastRequestOrderId) {
      return;
    }

    if (_orderRequestIndex >= sortedOrders.length) {
      _orderRequestIndex = 0;
    }

    final candidate = sortedOrders[_orderRequestIndex];
    _orderRequestIndex = (_orderRequestIndex + 1) % sortedOrders.length;

    _lastRequestOrderId = candidate.id;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _showOrderRequest(candidate);
    });
  }

  String? _distanceLabel(DriverOrder order) {
    if (_currentLocation == null ||
        order.vendor.latitude == null ||
        order.vendor.longitude == null) {
      return null;
    }

    final meters = Geolocator.distanceBetween(
      _currentLocation!.latitude,
      _currentLocation!.longitude,
      order.vendor.latitude!,
      order.vendor.longitude!,
    );

    return "${(meters / 1000).toStringAsFixed(1)} km";
  }

  List<DriverOrder> _sortedAvailableOrders() {
    if (_currentLocation == null) {
      return List<DriverOrder>.from(_availableOrders);
    }

    final origin = _currentLocation!;
    final sorted = List<DriverOrder>.from(_availableOrders);
    sorted.sort((a, b) {
      final aLat = a.vendor.latitude;
      final aLng = a.vendor.longitude;
      final bLat = b.vendor.latitude;
      final bLng = b.vendor.longitude;

      if (aLat == null || aLng == null) return 1;
      if (bLat == null || bLng == null) return -1;

      final aDistance = Geolocator.distanceBetween(
        origin.latitude,
        origin.longitude,
        aLat,
        aLng,
      );
      final bDistance = Geolocator.distanceBetween(
        origin.latitude,
        origin.longitude,
        bLat,
        bLng,
      );
      return aDistance.compareTo(bDistance);
    });
    return sorted;
  }

  String? _moneyLabel(MoneyAmount? amount) {
    if (amount == null) return null;
    return "${amount.amount} ${amount.currency}";
  }

  void _showOrderRequest(DriverOrder order) {
    if (_requestOpen) return;
    _requestOpen = true;

    final payout = _moneyLabel(order.vendor.deliveryFee) ??
        _moneyLabel(order.netTotal) ??
        _moneyLabel(order.grossTotal);

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return OrderRequestSheet(
          orderId: order.id,
          vendorName: order.vendor.businessName,
          pickupDistance: _distanceLabel(order),
          payout: payout,
          zone: order.zone,
          onAccept: () async {
            Navigator.of(context).pop();
            final service = OrderService(apiClient: AppScope.of(this.context).apiClient);
            final result = await service.acceptOrder(order.id);
            if (!mounted) return;
            ScaffoldMessenger.of(this.context).showSnackBar(
              SnackBar(
                content: Text(
                  result.success ? "Order accepted" : result.message,
                ),
              ),
            );
            if (result.success) {
              _openActiveOrder(order);
            }
          },
          onReject: () async {
            Navigator.of(context).pop();
            final service = OrderService(apiClient: AppScope.of(this.context).apiClient);
            final result = await service.rejectOrder(order.id);
            if (!mounted) return;
            ScaffoldMessenger.of(this.context).showSnackBar(
              SnackBar(
                content: Text(
                  result.success ? "Order rejected" : result.message,
                ),
              ),
            );
            _loadAvailableOrders();
          },
          onTimeout: () async {
            Navigator.of(context).pop();
            final service = OrderService(apiClient: AppScope.of(this.context).apiClient);
            await service.rejectOrder(order.id);
            if (!mounted) return;
            ScaffoldMessenger.of(this.context).showSnackBar(
              const SnackBar(content: Text("Order request expired")),
            );
            _loadAvailableOrders();
          },
        );
      },
    ).whenComplete(() => _requestOpen = false);
  }

  void _showOrderRequestFromAvailable() {
    if (_availableOrders.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("No available orders.")),
      );
      return;
    }
    _showOrderRequest(_sortedAvailableOrders().first);
  }

  void _openRouteOptimization() {
    if (_currentLocation == null || _destination == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("No active route to optimize.")),
      );
      return;
    }

    final orderLabel = _activeOrder == null
        ? null
        : "Order ${_activeOrder!.id} · ${_activeOrder!.vendor.businessName}";

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => RouteOptimizationScreen(
          origin: _currentLocation!,
          destination: _destination!,
          orderLabel: orderLabel,
        ),
      ),
    );
  }

  Future<void> _triggerSos() async {
    final reason = await _promptSosReason();
    if (!mounted) return;
    if (reason == null) return;

    final apiClient = AppScope.of(context).apiClient;
    final payload = <String, dynamic>{};
    if (reason.isNotEmpty) payload["reason"] = reason;
    if (_activeOrder?.id != null) payload["order_id"] = _activeOrder!.id;
    if (_currentLocation != null) {
      payload["latitude"] = _currentLocation!.latitude;
      payload["longitude"] = _currentLocation!.longitude;
    }

    try {
      final response = await apiClient.dio.post(ApiPaths.driverSos, data: payload);
      final success = response.statusCode == 201 || response.data["success"] == true;
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? "SOS sent. Check Messages (Settings) for admin response." : "Failed to send SOS alert.",
          ),
          duration: const Duration(seconds: 4),
        ),
      );
    } catch (_) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Unable to send SOS alert.")),
      );
    }
  }

  Future<String?> _promptSosReason() async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text("Emergency SOS"),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                "Send an SOS alert to the support team. Add a short reason if needed.",
              ),
              const SizedBox(height: 12),
              TextField(
                controller: controller,
                decoration: const InputDecoration(
                  hintText: "Reason (optional)",
                ),
                maxLines: 2,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text("Cancel"),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red.shade600,
              ),
              onPressed: () {
                Navigator.pop(context, controller.text.trim());
              },
              child: const Text("Send SOS"),
            ),
          ],
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            Positioned.fill(
              child: GoogleMap(
                initialCameraPosition: CameraPosition(
                  target: _currentLocation ?? _fallbackOrigin,
                  zoom: _currentLocation != null ? 14 : 12,
                ),
                onMapCreated: (GoogleMapController controller) {
                  _mapController = controller;
                  if (_currentLocation != null) {
                    // Small delay so map is ready for camera updates
                    Future.delayed(const Duration(milliseconds: 300), () {
                      if (mounted && _currentLocation != null) {
                        _moveCameraToDriver(_currentLocation!);
                      }
                    });
                  }
                },
                myLocationEnabled: true,
                myLocationButtonEnabled: true,
                markers: {
                  if (_currentLocation != null)
                    Marker(
                      markerId: const MarkerId("origin"),
                      position: _currentLocation!,
                    ),
                  if (_destination != null)
                    Marker(
                      markerId: const MarkerId("destination"),
                      position: _destination!,
                    ),
                },
                circles: _heatmapCircles,
                polylines: _polylines,
              ),
            ),
            Positioned(
              top: 16,
              right: 16,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                decoration: BoxDecoration(
                  color: scheme.surface.withOpacity(isDark ? 0.6 : 0.9),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(isDark ? 0.5 : 0.15),
                      blurRadius: 12,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: _showOrderRequestFromAvailable,
                      icon: const Icon(Icons.notifications_active),
                    ),
                    IconButton(
                      onPressed: _loadActiveOrder,
                      icon: const Icon(Icons.refresh),
                    ),
                    IconButton(
                      onPressed: _loadRoute,
                      icon: const Icon(Icons.alt_route),
                    ),
                    IconButton(
                      onPressed: _openRouteOptimization,
                      icon: const Icon(Icons.route),
                    ),
                    IconButton(
                      onPressed: _loadTodaySummary,
                      icon: const Icon(Icons.bar_chart),
                    ),
                    IconButton(
                      onPressed: _triggerSos,
                      icon: const Icon(Icons.warning_rounded, color: Colors.red),
                      tooltip: "SOS",
                    ),
                  ],
                ),
              ),
            ),
            Positioned(
              left: 16,
              right: 16,
              bottom: 16,
              child: GestureDetector(
                onTap: _cardMinimized ? () => setState(() => _cardMinimized = false) : null,
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 250),
                  curve: Curves.easeInOut,
                  padding: EdgeInsets.all(_cardMinimized ? 12 : 16),
                  decoration: BoxDecoration(
                    color: scheme.surface.withOpacity(isDark ? 0.72 : 0.96),
                    borderRadius: BorderRadius.circular(24),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(isDark ? 0.55 : 0.18),
                        blurRadius: 24,
                        offset: const Offset(0, 12),
                      ),
                    ],
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Text(
                            "Today",
                            style: TextStyle(
                              fontSize: _cardMinimized ? 16 : 18,
                              fontWeight: FontWeight.w700,
                              color: scheme.onSurface,
                            ),
                          ),
                          const Spacer(),
                          IconButton(
                            icon: Icon(
                              _cardMinimized ? Icons.expand_less : Icons.expand_more,
                              color: scheme.onSurfaceVariant,
                            ),
                            onPressed: () => setState(() => _cardMinimized = !_cardMinimized),
                            tooltip: _cardMinimized ? "Expand" : "Minimize",
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(minWidth: 36, minHeight: 36),
                          ),
                        ],
                      ),
                      if (!_cardMinimized) const SizedBox(height: 10),
                      Row(
                        children: [
                          Expanded(
                            child: _statItem(
                              "Trips",
                              _driverStats == null
                                  ? (_loadingSummary ? "..." : "-")
                                  : _driverStats!.totalDelivered.toString(),
                              compact: _cardMinimized,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: _statItem(
                              "Earnings",
                              _earningsSummary == null
                                  ? (_loadingSummary ? "..." : "-")
                                  : _formatMoney(_earningsSummary!),
                              compact: _cardMinimized,
                            ),
                          ),
                        ],
                      ),
                      if (!_cardMinimized) ...[
                        if (_activeOrder != null) ...[
                          const SizedBox(height: 12),
                          _buildActiveOrderBanner(),
                        ],
                        const SizedBox(height: 12),
                        _buildActiveOrderRow(),
                        if (_routeMessage != null) ...[
                          const SizedBox(height: 8),
                          Text(
                            _routeMessage!,
                            style: TextStyle(
                              color: scheme.onSurfaceVariant,
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        SwitchListTile(
                          contentPadding: EdgeInsets.zero,
                          title: const Text("Go online"),
                          value: _online,
                          onChanged: _saving ? null : _toggleOnline,
                        ),
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: _showOrderRequestFromAvailable,
                            child: const Text("Show order request"),
                          ),
                        ),
                      ] else ...[
                        const SizedBox(height: 8),
                        Row(
                          children: [
                            Text(
                              "Go online",
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w500,
                                color: scheme.onSurface,
                              ),
                            ),
                            const SizedBox(width: 8),
                            Switch(
                              value: _online,
                              onChanged: _saving ? null : _toggleOnline,
                            ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _statItem(String label, String value, {bool compact = false}) {
    return Container(
      padding: EdgeInsets.all(compact ? 8 : 12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.primary.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: TextStyle(
              color: Theme.of(context).colorScheme.primary,
              fontWeight: FontWeight.w600,
              fontSize: compact ? 12 : null,
            ),
          ),
          SizedBox(height: compact ? 4 : 6),
          Text(
            value,
            style: TextStyle(
              fontSize: compact ? 14 : 16,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  String _formatMoney(EarningsSummary summary) {
    final amount = summary.totals.net;
    final currency = summary.currency.isEmpty ? "" : " ${summary.currency}";
    return "$amount$currency";
  }

  Widget _buildActiveOrderRow() {
    if (_loadingOrder) {
      return const Text("Loading active order...");
    }

    if (_activeOrder == null) {
      return const Text("No active order assigned.");
    }

    return Row(
      children: [
        Expanded(
          child: Text(
            _activeOrder!.vendor.businessName,
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
        ),
        _statusBadge(_activeOrder!.status),
      ],
    );
  }

  Widget _buildActiveOrderBanner() {
    if (_activeOrder == null) return const SizedBox.shrink();

    final order = _activeOrder!;
    final isDeliveryStage = _isDeliveryStage(order.status);
    final buttonLabel = isDeliveryStage ? "Go to delivery" : "Go to pickup";

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              "Active order",
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(order.vendor.businessName),
            const SizedBox(height: 8),
            Row(
              children: [
                _statusBadge(order.status),
                const SizedBox(width: 8),
                Text("Order ${order.id}"),
              ],
            ),
            const SizedBox(height: 12),
            _buildStatusTimeline(order.status),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: () => _openActiveOrder(order),
                child: Text(buttonLabel),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openActiveOrder(DriverOrder order) {
    if (_isDeliveryStage(order.status)) {
      Navigator.of(context).push(
        MaterialPageRoute(
          builder: (_) => ActiveDeliveryScreen(
            order: order,
          ),
        ),
      );
      return;
    }

    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => ActivePickupScreen(
            order: order,
        ),
      ),
    );
  }

  Widget _statusBadge(String status) {
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

  Widget _buildStatusTimeline(String status) {
    final step = _statusStep(status);
    return Row(
      children: [
        _timelineItem("Pickup", step >= 0, step == 0),
        _timelineDivider(),
        _timelineItem("Out for delivery", step >= 1, step == 1),
        _timelineDivider(),
        _timelineItem("Delivered", step >= 2, step == 2),
      ],
    );
  }

  Widget _timelineItem(String label, bool complete, bool active) {
    final color = complete
        ? Theme.of(context).colorScheme.primary
        : Theme.of(context).colorScheme.outline;
    final icon = complete
        ? Icons.check_circle
        : active
            ? Icons.radio_button_checked
            : Icons.radio_button_unchecked;

    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(height: 4),
          Text(
            label,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: color),
          ),
        ],
      ),
    );
  }

  Widget _timelineDivider() {
    return Expanded(
      child: Container(
        height: 2,
        margin: const EdgeInsets.symmetric(horizontal: 6),
        color: Theme.of(context).colorScheme.outline.withOpacity(0.5),
      ),
    );
  }
}
