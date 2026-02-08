import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/payment_details_service.dart";
import "../models/payment_details_models.dart";

class BankAccountScreen extends StatefulWidget {
  const BankAccountScreen({super.key});

  @override
  State<BankAccountScreen> createState() => _BankAccountScreenState();
}

class _BankAccountScreenState extends State<BankAccountScreen> {
  final _accountNumberController = TextEditingController();
  final _accountNameController = TextEditingController();

  List<BankInfo> _banks = [];
  BankInfo? _selectedBank;
  DriverPaymentDetail? _detail;
  bool _loading = true;
  bool _saving = false;
  bool _resolving = false;
  String? _error;

  @override
  void dispose() {
    _accountNumberController.dispose();
    _accountNameController.dispose();
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final service = PaymentDetailsService(apiClient: AppScope.of(context).apiClient);
    try {
      final banksResult = await service.fetchBanks();
      final detailResult = await service.fetchPaymentDetail();

      if (!mounted) return;

      setState(() {
        _banks = banksResult.data ?? [];
        _detail = detailResult.data;
        _error = banksResult.success ? null : banksResult.message;
      });

      if (_detail != null) {
        _accountNumberController.text = _detail!.accountNumber;
        _accountNameController.text = _detail!.accountName;
        _selectedBank = _banks.firstWhere(
          (bank) => bank.code == _detail!.bankCode,
          orElse: () => BankInfo(
            name: _detail!.bankName,
            code: _detail!.bankCode,
          ),
        );
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Failed to load bank details.");
    } finally {
      if (!mounted) return;
      setState(() => _loading = false);
    }
  }

  Future<void> _resolveAccount() async {
    final accountNumber = _accountNumberController.text.trim();
    if (accountNumber.isEmpty || _selectedBank == null) {
      setState(() => _error = "Select a bank and enter account number.");
      return;
    }

    setState(() {
      _resolving = true;
      _error = null;
    });

    final service = PaymentDetailsService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.resolveAccount(
        accountNumber: accountNumber,
        bankCode: _selectedBank!.code,
      );

      if (!mounted) return;

      if (result.success && result.data != null) {
        _accountNameController.text =
            (result.data!["account_name"] ?? "").toString();
      } else {
        setState(() => _error = result.message.isEmpty ? "Resolve failed." : result.message);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Resolve failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _resolving = false);
    }
  }

  Future<void> _save() async {
    final accountNumber = _accountNumberController.text.trim();
    final accountName = _accountNameController.text.trim();
    if (accountNumber.isEmpty || accountName.isEmpty || _selectedBank == null) {
      setState(() => _error = "Complete all fields.");
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    final service = PaymentDetailsService(apiClient: AppScope.of(context).apiClient);
    try {
      final result = await service.savePaymentDetail(
        accountNumber: accountNumber,
        bankCode: _selectedBank!.code,
        accountName: accountName,
      );

      if (!mounted) return;

      if (result.success) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Bank account updated")),
        );
        setState(() => _detail = result.data);
      } else {
        setState(() => _error = result.message.isEmpty ? "Save failed." : result.message);
      }
    } catch (_) {
      if (!mounted) return;
      setState(() => _error = "Save failed. Try again.");
    } finally {
      if (!mounted) return;
      setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Bank account")),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Payout account",
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                            ),
                            const SizedBox(height: 12),
                            DropdownButtonFormField<BankInfo>(
                              value: _selectedBank,
                              items: _banks
                                  .map(
                                    (bank) => DropdownMenuItem<BankInfo>(
                                      value: bank,
                                      child: Text(bank.name),
                                    ),
                                  )
                                  .toList(),
                              onChanged: (value) {
                                setState(() => _selectedBank = value);
                              },
                              decoration: const InputDecoration(labelText: "Bank"),
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _accountNumberController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(labelText: "Account number"),
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _accountNameController,
                              decoration: const InputDecoration(labelText: "Account name"),
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: OutlinedButton(
                                onPressed: _resolving ? null : _resolveAccount,
                                child: Text(_resolving ? "Resolving..." : "Resolve account"),
                              ),
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              child: FilledButton(
                                onPressed: _saving ? null : _save,
                                child: Text(_saving ? "Saving..." : "Save bank account"),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}
