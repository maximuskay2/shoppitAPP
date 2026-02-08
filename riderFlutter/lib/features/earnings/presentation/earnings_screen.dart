import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/earnings_service.dart";
import "../models/earnings_models.dart";
import "wallet_screen.dart";
import "driver_stats_screen.dart";

// ---------------------------------------------------------------------------
// DESIGN SYSTEM CONSTANTS
// ---------------------------------------------------------------------------
const Color kPrimaryColor = Color(0xFF2C9139); // Brand Green
const Color kBackgroundColor = Color(0xFFF8F9FD);
const Color kSurfaceColor = Color(0xFFFFFFFF);
const Color kTextDark = Color(0xFF1A1D26);
const Color kTextLight = Color(0xFF9EA3AE);
const Color kAccentBlue = Color(0xFF2979FF);
const Color kAccentPurple = Color(0xFF651FFF);
const Color kAccentOrange = Color(0xFFFF9100);

class EarningsScreen extends StatefulWidget {
  const EarningsScreen({super.key});

  @override
  State<EarningsScreen> createState() => _EarningsScreenState();
}

class _EarningsScreenState extends State<EarningsScreen> {
  EarningsSummary? _summary;
  DriverStats? _stats;
  List<EarningsHistoryItem> _history = [];
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadEarnings();
  }

  Future<void> _loadEarnings() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = EarningsService(apiClient: AppScope.of(context).apiClient);

    try {
      final summaryResult = await service.fetchSummary();
      final statsResult = await service.fetchStats();
      final historyResult = await service.fetchHistory(perPage: 20);

      if (!mounted) return;

      setState(() {
        _summary = summaryResult.data;
        _stats = statsResult.data;
        _history = historyResult.data ?? [];
        _error = summaryResult.success ? null : summaryResult.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load earnings.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  // -------------------------------------------------------------------------
  // UI BUILD
  // -------------------------------------------------------------------------
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kBackgroundColor,
      // Using standard AppBar here for simplicity within the tab shell, 
      // but styled to match the theme.
      appBar: AppBar(
        backgroundColor: kBackgroundColor,
        elevation: 0,
        centerTitle: false,
        title: const Text(
          "Earnings",
          style: TextStyle(
            color: kTextDark,
            fontWeight: FontWeight.w800,
            fontSize: 24,
          ),
        ),
        actions: [
          IconButton(
            onPressed: _loadEarnings,
            icon: Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: kSurfaceColor,
                shape: BoxShape.circle,
                boxShadow: [
                  BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10),
                ],
              ),
              child: const Icon(Icons.refresh, color: kTextDark, size: 20),
            ),
          ),
          const SizedBox(width: 16),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryColor))
          : _error != null
              ? Center(child: Text(_error!, style: const TextStyle(color: Colors.red)))
              : RefreshIndicator(
                  onRefresh: _loadEarnings,
                  color: kPrimaryColor,
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 10, 20, 100), // Bottom padding for FAB/Nav
                    physics: const BouncingScrollPhysics(),
                    children: [
                      // --- 1. The "Credit Card" Wallet ---
                      _buildWalletCard(context),
                      const SizedBox(height: 24),
                      
                      // --- 2. Financial Summary Grid ---
                      if (_summary != null) ...[
                         const Text(
                          "Financial Overview",
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
                        ),
                        const SizedBox(height: 12),
                        _buildSummaryGrid(_summary!),
                      ],
                      const SizedBox(height: 24),

                      // --- 3. Delivery Stats Grid ---
                      if (_stats != null) ...[
                        const Text(
                          "Performance",
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
                        ),
                        const SizedBox(height: 12),
                        _buildStatsGrid(_stats!),
                      ],
                      const SizedBox(height: 24),

                      // --- 4. Recent History List ---
                      const Text(
                        "Recent Activity",
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: kTextDark),
                      ),
                      const SizedBox(height: 12),
                      if (_history.isEmpty)
                        _buildEmptyHistory()
                      else
                        ..._history.map(_buildHistoryTile),
                    ],
                  ),
                ),
    );
  }

  // --- 1. Wallet Card (The Hero) ---
  Widget _buildWalletCard(BuildContext context) {
    return Container(
      width: double.infinity,
      height: 180,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2C3E50), Color(0xFF000000)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Stack(
        children: [
          // Decorative Circles
          Positioned(
            top: -20, right: -20,
            child: Container(
              width: 150, height: 150,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withOpacity(0.05),
              ),
            ),
          ),
          
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      "Total Balance",
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.2),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Text("Active", style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                    )
                  ],
                ),
                
                // Balance
                Text(
                  _summary != null 
                    ? "${_summary!.totals.net} ${_summary!.currency}"
                    : "---",
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 32,
                    fontWeight: FontWeight.w800,
                  ),
                ),

                // Action Button
                GestureDetector(
                  onTap: () {
                    Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => const WalletScreen()),
                    );
                  },
                  child: Row(
                    children: [
                      const Text(
                        "Go to Wallet",
                        style: TextStyle(color: kPrimaryColor, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.arrow_forward, color: kPrimaryColor, size: 16),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // --- 2. Summary Grid ---
  Widget _buildSummaryGrid(EarningsSummary summary) {
    return Row(
      children: [
        Expanded(
          child: _buildStatBox(
            label: "Gross Income",
            value: "${summary.totals.gross}",
            icon: Icons.attach_money,
            color: kAccentBlue,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: _buildStatBox(
            label: "Pending",
            value: "${summary.byStatus.pending}",
            icon: Icons.pending_outlined,
            color: kAccentOrange,
          ),
        ),
      ],
    );
  }

  // --- 3. Stats Grid ---
  Widget _buildStatsGrid(DriverStats stats) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatBox(
                label: "Completed",
                value: stats.totalDelivered.toString(),
                icon: Icons.check_circle_outline,
                color: kPrimaryColor,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatBox(
                label: "Completion Rate",
                value: "${stats.completionRate}%",
                icon: Icons.show_chart,
                color: kAccentPurple,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Full width stats button
         GestureDetector(
          onTap: () {
            Navigator.of(context).push(
              MaterialPageRoute(builder: (_) => const DriverStatsScreen()),
            );
          },
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 16),
            decoration: BoxDecoration(
              color: kSurfaceColor,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: kPrimaryColor.withOpacity(0.3)),
            ),
            child: const Center(
              child: Text(
                "View Detailed Analytics",
                style: TextStyle(
                  color: kPrimaryColor,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  // --- Helper: Stat Box ---
  Widget _buildStatBox({
    required String label, 
    required String value, 
    required IconData icon, 
    required Color color
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
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
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: kTextDark,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: kTextLight,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  // --- 4. History List ---
  Widget _buildHistoryTile(EarningsHistoryItem item) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurfaceColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: kBackgroundColor,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.receipt_long, color: kTextLight, size: 20),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.trackingId ?? "Transaction",
                  style: const TextStyle(fontWeight: FontWeight.bold, color: kTextDark),
                ),
                const SizedBox(height: 4),
                Text(
                  item.status.toUpperCase(),
                  style: const TextStyle(fontSize: 10, color: kTextLight, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
          Text(
            "+ ${item.netAmount}",
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              color: kPrimaryColor,
              fontSize: 16,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyHistory() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          children: [
            Icon(Icons.history, size: 48, color: kTextLight.withOpacity(0.5)),
            const SizedBox(height: 8),
            const Text("No recent activity", style: TextStyle(color: kTextLight)),
          ],
        ),
      ),
    );
  }
}