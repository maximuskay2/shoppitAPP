import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/payout_service.dart";
import "../models/payout_models.dart";
import "bank_account_screen.dart";

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  PayoutBalance? _balance;
  List<DriverPayout> _payouts = [];
  bool _loading = true;
  String? _error;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadWallet();
  }

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
    final service = PayoutService(apiClient: AppScope.of(context).apiClient);
    final result = await service.requestPayout();
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result.success ? "Payout request submitted" : result.message,
        ),
      ),
    );

    _loadWallet();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Wallet & payouts")),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : RefreshIndicator(
                  onRefresh: _loadWallet,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "Withdrawable balance",
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                _balance == null
                                    ? "-"
                                    : "${_balance!.withdrawable} ${_balance!.currency}",
                                style: const TextStyle(
                                  fontSize: 22,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 12),
                              SizedBox(
                                width: double.infinity,
                                child: FilledButton(
                                  onPressed: _requestPayout,
                                  child: const Text("Request payout"),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Card(
                        child: ListTile(
                          title: const Text("Bank account"),
                          subtitle: const Text("Add or update payout account"),
                          trailing: const Icon(Icons.chevron_right),
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => const BankAccountScreen(),
                              ),
                            );
                          },
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
                                "Recent payouts",
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 12),
                              if (_payouts.isEmpty)
                                const Text("No payouts yet.")
                              else
                                ..._payouts.map(_buildPayoutTile),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildPayoutTile(DriverPayout payout) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      title: Text("${payout.amount} ${payout.currency}"),
      subtitle: Text(payout.status),
      trailing: Text(payout.createdAt ?? ""),
    );
  }
}
