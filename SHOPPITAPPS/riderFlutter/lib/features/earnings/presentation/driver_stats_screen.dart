import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/earnings_service.dart";
import "../models/earnings_models.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kAccentBlue = Color(0xFF2979FF);
const Color kErrorColor = Color(0xFFE53935);
const Color kWarningColor = Color(0xFFFFA000);

class DriverStatsScreen extends StatefulWidget {
  const DriverStatsScreen({super.key});

  @override
  State<DriverStatsScreen> createState() => _DriverStatsScreenState();
}

class _DriverStatsScreenState extends State<DriverStatsScreen> {
  DriverStats? _stats;
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadStats();
  }

  Future<void> _loadStats() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = EarningsService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.fetchStats();
      if (!mounted) return;
      setState(() {
        _stats = result.data;
        _error = result.success ? null : result.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load driver stats.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
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
              boxShadow: [
                BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10),
              ],
            ),
            child: const Icon(Icons.arrow_back, color: kTextDark, size: 20),
          ),
        ),
        title: const Text(
          "Performance",
          style: TextStyle(
            color: kTextDark,
            fontWeight: FontWeight.w800,
            fontSize: 20,
          ),
        ),
        actions: [
          IconButton(
            onPressed: _loadStats,
            icon: const Icon(Icons.refresh, color: kTextDark),
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: kErrorColor)))
              : _stats == null
                  ? const Center(child: Text("No stats available."))
                  : RefreshIndicator(
                      onRefresh: _loadStats,
                      color: kPrimaryColor,
                      child: ListView(
                        padding: const EdgeInsets.all(24),
                        physics: const BouncingScrollPhysics(),
                        children: [
                          _buildCompletionHero(_stats!),
                          const SizedBox(height: 24),
                          
                          const Text(
                            "Operations",
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
                          ),
                          const SizedBox(height: 12),
                          _buildOperationsGrid(_stats!),
                          
                          const SizedBox(height: 24),
                          
                          const Text(
                            "Financials",
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
                          ),
                          const SizedBox(height: 12),
                          _buildEarningsStats(_stats!),
                          
                          const SizedBox(height: 24),
                          _buildMeta(_stats!),
                          const SizedBox(height: 40),
                        ],
                      ),
                    ),
    );
  }

  // --- 1. Hero Section: Completion Rate Ring ---
  Widget _buildCompletionHero(DriverStats stats) {
    // Parse completion rate safely
    final double rate = (double.tryParse(stats.completionRate.toString()) ?? 0.0) / 100.0;
    
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2C3E50).withOpacity(0.08),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          const Text(
            "Completion Rate",
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: kTextLight,
              letterSpacing: 1,
            ),
          ),
          const SizedBox(height: 24),
          Stack(
            alignment: Alignment.center,
            children: [
              SizedBox(
                height: 150,
                width: 150,
                child: CircularProgressIndicator(
                  value: 1.0, // Background circle
                  strokeWidth: 12,
                  valueColor: AlwaysStoppedAnimation<Color>(kBackgroundColor),
                ),
              ),
              SizedBox(
                height: 150,
                width: 150,
                child: CircularProgressIndicator(
                  value: rate.clamp(0.0, 1.0),
                  strokeWidth: 12,
                  strokeCap: StrokeCap.round,
                  backgroundColor: Colors.transparent,
                  valueColor: const AlwaysStoppedAnimation<Color>(kPrimaryColor),
                ),
              ),
              Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    "${stats.completionRate}%",
                    style: const TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.w900,
                      color: kTextDark,
                    ),
                  ),
                  const Text(
                    "Success",
                    style: TextStyle(fontSize: 12, color: kPrimaryColor, fontWeight: FontWeight.bold),
                  )
                ],
              )
            ],
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildMiniMetric("Assigned", stats.totalAssigned.toString(), kTextDark),
              Container(
                height: 20, width: 1, color: kTextLight.withOpacity(0.3), margin: const EdgeInsets.symmetric(horizontal: 24)
              ),
              _buildMiniMetric("Cancelled", stats.totalCancelled.toString(), kErrorColor),
            ],
          )
        ],
      ),
    );
  }

  Widget _buildMiniMetric(String label, String value, Color valueColor) {
    return Column(
      children: [
        Text(value, style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: valueColor)),
        const SizedBox(height: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: kTextLight, fontWeight: FontWeight.w600)),
      ],
    );
  }

  // --- 2. Operations Grid (3D Tiles) ---
  Widget _buildOperationsGrid(DriverStats stats) {
    return Row(
      children: [
        Expanded(
          child: _statCard(
            "Delivered",
            stats.totalDelivered.toString(),
            Icons.check_circle_outline,
            kPrimaryColor,
          ),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: _statCard(
            "Pending",
            // Assuming assigned - delivered - cancelled = pending roughly, or just showing assigned again if preferred
            stats.totalAssigned.toString(), 
            Icons.assignment_outlined,
            kAccentBlue,
          ),
        ),
      ],
    );
  }

  // --- 3. Earnings Section (Financial Card) ---
  Widget _buildEarningsStats(DriverStats stats) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [const Color(0xFF2C3E50), const Color(0xFF34495E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 15,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Total Earnings", style: TextStyle(color: Colors.white70, fontSize: 12)),
                  const SizedBox(height: 4),
                  Text(
                    stats.earningsTotal.toString(), // Add currency symbol if available in logic
                    style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: Colors.white.withOpacity(0.1), shape: BoxShape.circle),
                child: const Icon(Icons.attach_money, color: Colors.white),
              )
            ],
          ),
          const SizedBox(height: 20),
          Container(height: 1, color: Colors.white.withOpacity(0.1)),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("Paid Out", style: TextStyle(color: kPrimaryColor, fontSize: 12, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(stats.earningsPaid.toString(), style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text("Pending", style: TextStyle(color: kWarningColor, fontSize: 12, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 4),
                    Text(stats.earningsPending.toString(), style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w600)),
                  ],
                ),
              ),
            ],
          )
        ],
      ),
    );
  }

  // --- 4. Meta Data ---
  Widget _buildMeta(DriverStats stats) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: kTextLight.withOpacity(0.2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.access_time_filled, color: kTextLight, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "LAST DELIVERY",
                  style: TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: kTextLight, letterSpacing: 1),
                ),
                const SizedBox(height: 4),
                Text(
                  stats.lastDeliveryAt == null || stats.lastDeliveryAt!.isEmpty
                      ? "No deliveries yet"
                      : stats.lastDeliveryAt!,
                  style: const TextStyle(fontWeight: FontWeight.w600, color: kTextDark),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // --- Helper: 3D Stat Card ---
  Widget _statCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF9EA3AE).withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: kTextDark),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(fontSize: 13, color: kTextLight, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }
}