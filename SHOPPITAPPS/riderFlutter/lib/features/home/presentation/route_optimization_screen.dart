import "package:flutter/material.dart";
import "package:google_maps_flutter/google_maps_flutter.dart";

import "../../../app/app_scope.dart";
import "../data/navigation_service.dart";
import "../models/route_models.dart";

class RouteOptimizationScreen extends StatefulWidget {
  const RouteOptimizationScreen({
    super.key,
    required this.origin,
    required this.destination,
    this.orderLabel,
  });

  final LatLng origin;
  final LatLng destination;
  final String? orderLabel;

  @override
  State<RouteOptimizationScreen> createState() =>
      _RouteOptimizationScreenState();
}

class _RouteOptimizationScreenState extends State<RouteOptimizationScreen> {
  bool _loading = false;
  String? _message;
  RouteInfo? _route;

  @override
  void initState() {
    super.initState();
    _loadRoute();
  }

  Future<void> _loadRoute() async {
    if (_loading) return;
    setState(() {
      _loading = true;
      _message = null;
    });

    final service = NavigationService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchRoute(
        originLat: widget.origin.latitude,
        originLng: widget.origin.longitude,
        destinationLat: widget.destination.latitude,
        destinationLng: widget.destination.longitude,
      );

      if (!mounted) return;

      if (result.success && result.data != null) {
        setState(() => _route = result.data);
      } else {
        setState(() {
          _route = null;
          _message = result.message.isEmpty
              ? "Unable to load optimized route."
              : result.message;
        });
      }
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _route = null;
        _message = "Unable to load optimized route.";
      });
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final polylines = _route == null
        ? <Polyline>{}
        : {
            Polyline(
              polylineId: const PolylineId("optimized_route"),
              color: Theme.of(context).colorScheme.primary,
              width: 5,
              points: _route!.polyline
                  .map((point) => LatLng(point.lat, point.lng))
                  .toList(),
            ),
          };

    return Scaffold(
      appBar: AppBar(
        title: const Text("Route optimization"),
        actions: [
          IconButton(
            onPressed: _loadRoute,
            icon: const Icon(Icons.refresh),
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (widget.orderLabel != null) ...[
                    Text(
                      widget.orderLabel!,
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 6),
                  ],
                  Text(
                    _route == null
                        ? (_loading ? "Loading route..." : "No route loaded")
                        : "${_route!.distanceKm} km · ${_route!.etaMinutes} min",
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  if (_message != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      _message!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ],
                  if (_route?.note != null && _route!.note!.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Text(
                      _route!.note!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            Expanded(
              child: GoogleMap(
                initialCameraPosition: CameraPosition(
                  target: widget.origin,
                  zoom: 13,
                ),
                markers: {
                  Marker(
                    markerId: const MarkerId("origin"),
                    position: widget.origin,
                  ),
                  Marker(
                    markerId: const MarkerId("destination"),
                    position: widget.destination,
                  ),
                },
                polylines: polylines,
                myLocationEnabled: true,
                myLocationButtonEnabled: true,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
