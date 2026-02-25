import "package:flutter/material.dart";

// ---------------------------------------------------------------------------
// IMPORTS
// ---------------------------------------------------------------------------
import "../../../app/app_scope.dart";
import "../data/payout_service.dart";
import "../models/payout_models.dart";
import "bank_account_screen.dart";

// ---------------------------------------------------------------------------
// DARK THEME CONSTANTS (DARK COCKPIT)
// ---------------------------------------------------------------------------
const Color kPrimaryGreen = Color(0xFF4CE5B1);
const Color kDarkBg = Color(0xFF0F1115);
const Color kSurfaceDark = Color(0xFF1F222A);
const Color kTextWhite = Color(0xFFFFFFFF);
const Color kTextGrey = Color(0xFF9E9E9E);
const Color kAccentOrange = Color(0xFFFFA000);
const Color kErrorColor = Color(0xFFE53935);

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  PayoutBalance? _balance;
  List<DriverPayout> _payouts = [];
  bool _loading = true;
  bool _isRequesting = false;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadWallet();
  }

  // --- LOGIC (PRESERVED) ---
  Future<void> _loadWallet() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = PayoutService(apiClient: AppScope.of(context).apiClient);
    try {
      final balanceResult = await service.fetchBalance();
      final payoutsResult = await service.fetchPayouts();

      if (!mounted) return;

      setState(() {
        _balance = balanceResult.data;
        _payouts = payoutsResult.data ?? [];
        _error = balanceResult.success ? null : balanceResult.message;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load wallet.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _requestPayout() async {
    setState(() => _isRequesting = true);
    
    final service = PayoutService(apiClient: AppScope.of(context).apiClient);
    final result = await service.requestPayout();
    
    if (!mounted) return;
    
    setState(() => _isRequesting = false);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result.success ? "Payout request submitted" : result.message,
          style: TextStyle(color: result.success ? kDarkBg : Colors.white, fontWeight: FontWeight.bold),
        ),
        backgroundColor: result.success ? kPrimaryGreen : kErrorColor,
        behavior: SnackBarBehavior.floating,
      ),
    );

    _loadWallet();
  }

  // --- UI BUILD ---
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: kDarkBg,
      appBar: AppBar(
        backgroundColor: kDarkBg,
        elevation: 0,
        centerTitle: true,
        leading: GestureDetector(
          onTap: () => Navigator.pop(context),
          child: Container(
            margin: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: kSurfaceDark,
              shape: BoxShape.circle,
              border: Border.all(color: Colors.white.withOpacity(0.05)),
            ),
            child: const Icon(Icons.arrow_back, color: kTextWhite, size: 20),
          ),
        ),
        title: const Text(
          "Wallet & Payouts",
          style: TextStyle(color: kTextWhite, fontWeight: FontWeight.w800),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator(color: kPrimaryGreen))
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline, color: kErrorColor, size: 48),
                      const SizedBox(height: 16),
                      Text(_error!, style: const TextStyle(color: kTextWhite)),
                      const SizedBox(height: 16),
                      OutlinedButton(
                        onPressed: _loadWallet,
                        style: OutlinedButton.styleFrom(foregroundColor: kPrimaryGreen, side: const BorderSide(color: kPrimaryGreen)),
                        child: const Text("Retry"),
                      )
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadWallet,
                  color: kPrimaryGreen,
                  backgroundColor: kSurfaceDark,
                  child: ListView(
                    padding: const EdgeInsets.all(24),
                    physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                    children: [
                      // --- 1. The "Black Card" Hero Balance ---
                      _buildBalanceCard(),
                      
                      const SizedBox(height: 24),
                      
                      // --- 2. Bank Account Action Tile ---
                      _buildActionTile(
                        icon: Icons.account_balance_rounded,
                        title: "Bank Account",
                        subtitle: "Add or update payout account",
                        onTap: () {
                          Navigator.of(context).push(
                            MaterialPageRoute(builder: (_) => const BankAccountScreen()),
                          );
                        },
                      ),
                      
                      const SizedBox(height: 32),
                      
                      // --- 3. Recent Payouts Header ---
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            "Recent Payouts",
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: kTextWhite,
                            ),
                          ),
                          Text(
                            "${_payouts.length} total",
                            style: const TextStyle(color: kTextGrey, fontSize: 12, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      
                      // --- 4. Payouts List ---
                      if (_payouts.isEmpty)
                        _buildEmptyState()
                      else
                        ..._payouts.map(_buildPayoutTile),
                        
                      const SizedBox(height: 40),
                    ],
                  ),
                ),
    );
  }

  // --- HELPER WIDGETS ---

  Widget _buildBalanceCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2C3E50), Color(0xFF1F222A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.4),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
        border: Border.all(color: Colors.white.withOpacity(0.05)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                "Withdrawable Balance",
                style: TextStyle(color: kTextGrey, fontSize: 14, fontWeight: FontWeight.w600),
              ),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: kPrimaryGreen.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.account_balance_wallet, color: kPrimaryGreen, size: 20),
              )
            ],
          ),
          const SizedBox(height: 8),
          Text(
            _balance == null ? "---" : "${_balance!.withdrawable} ${_balance!.currency}",
            style: const TextStyle(
              fontSize: 36,
              fontWeight: FontWeight.w900,
              color: kTextWhite,
              letterSpacing: -1,
            ),
          ),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            height: 56,
            child: ElevatedButton(
              onPressed: _isRequesting ? null : _requestPayout,
              style: ElevatedButton.styleFrom(
                backgroundColor: kPrimaryGreen,
                foregroundColor: Colors.black,
                elevation: 0,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              ),
              child: _isRequesting
                  ? const SizedBox(height: 24, width: 24, child: CircularProgressIndicator(color: Colors.black, strokeWidth: 2))
                  : const Text("Request Payout", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildActionTile({
    required IconData icon,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: kSurfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.05)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.2),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.blueAccent.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: Colors.blueAccent, size: 24),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700, color: kTextWhite)),
                      const SizedBox(height: 4),
                      Text(subtitle, style: const TextStyle(fontSize: 13, color: kTextGrey)),
                    ],
                  ),
                ),
                const Icon(Icons.arrow_forward_ios_rounded, color: kTextGrey, size: 16),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPayoutTile(DriverPayout payout) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurfaceDark,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.white.withOpacity(0.03)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: kDarkBg,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.receipt_long_rounded, color: kTextGrey, size: 20),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  "${payout.amount} ${payout.currency}",
                  style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: kTextWhite),
                ),
                const SizedBox(height: 4),
                Text(
                  payout.createdAt ?? "Unknown Date",
                  style: const TextStyle(fontSize: 12, color: kTextGrey, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
          _buildStatusBadge(payout.status),
        ],
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color bg = kTextGrey.withOpacity(0.1);
    Color text = kTextGrey;
    String label = status.toUpperCase();

    if (label.contains("PAID") || label.contains("SUCCESS")) {
      bg = kPrimaryGreen.withOpacity(0.1);
      text = kPrimaryGreen;
    } else if (label.contains("PENDING") || label.contains("PROCESSING")) {
      bg = kAccentOrange.withOpacity(0.1);
      text = kAccentOrange;
    } else if (label.contains("FAILED") || label.contains("REJECTED")) {
      bg = kErrorColor.withOpacity(0.1);
      text = kErrorColor;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: text, letterSpacing: 0.5),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 40),
      alignment: Alignment.center,
      child: Column(
        children: [
          Icon(Icons.history_rounded, size: 64, color: kTextGrey.withOpacity(0.3)),
          const SizedBox(height: 16),
          const Text("No payouts yet", style: TextStyle(color: kTextWhite, fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          const Text("Your withdrawal history will appear here.", style: TextStyle(color: kTextGrey, fontSize: 13)),
        ],
      ),
    );
  }
}