import "package:flutter/material.dart";

import "../../../app/app_scope.dart";
import "../data/payment_details_service.dart";
import "../models/payment_details_models.dart";

/// Fallback Nigerian banks when API fails (Paystack bank codes).
const List<BankInfo> _fallbackBanks = [
  BankInfo(name: "Access Bank", code: "044"),
  BankInfo(name: "Citibank Nigeria", code: "023"),
  BankInfo(name: "Ecobank Nigeria", code: "050"),
  BankInfo(name: "Fidelity Bank", code: "070"),
  BankInfo(name: "First Bank of Nigeria", code: "011"),
  BankInfo(name: "First City Monument Bank", code: "214"),
  BankInfo(name: "Globus Bank", code: "00103"),
  BankInfo(name: "Guaranty Trust Bank", code: "058"),
  BankInfo(name: "Heritage Bank", code: "030"),
  BankInfo(name: "Keystone Bank", code: "082"),
  BankInfo(name: "Kuda Microfinance Bank", code: "090267"),
  BankInfo(name: "Polaris Bank", code: "076"),
  BankInfo(name: "Providus Bank", code: "101"),
  BankInfo(name: "Stanbic IBTC Bank", code: "221"),
  BankInfo(name: "Standard Chartered Bank", code: "068"),
  BankInfo(name: "Sterling Bank", code: "232"),
  BankInfo(name: "Suntrust Bank", code: "100"),
  BankInfo(name: "Union Bank of Nigeria", code: "032"),
  BankInfo(name: "United Bank for Africa", code: "033"),
  BankInfo(name: "Unity Bank", code: "215"),
  BankInfo(name: "Wema Bank", code: "035"),
  BankInfo(name: "Zenith Bank", code: "057"),
];

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
    List<BankInfo> banks = [];
    DriverPaymentDetail? detail;

    try {
      final banksResult = await service.fetchBanks();
      if (banksResult.success && banksResult.data != null && banksResult.data!.isNotEmpty) {
        banks = banksResult.data!;
      } else {
        banks = _fallbackBanks;
      }
    } catch (_) {
      banks = _fallbackBanks;
    }

    try {
      final detailResult = await service.fetchPaymentDetail();
      if (detailResult.success) {
        detail = detailResult.data;
      }
    } catch (_) {
      detail = null;
    }

    if (!mounted) return;

    BankInfo? selectedBank;
    List<BankInfo> finalBanks = banks;
    if (detail != null) {
      _accountNumberController.text = detail.accountNumber;
      _accountNameController.text = detail.accountName;
      BankInfo? match;
      for (final b in banks) {
        if (b.code == detail.bankCode) {
          match = b;
          break;
        }
      }
      if (match != null) {
        selectedBank = match;
      } else {
        final customBank = BankInfo(name: detail.bankName, code: detail.bankCode);
        finalBanks = [customBank, ...banks];
        selectedBank = customBank;
      }
    } else if (banks.isNotEmpty) {
      selectedBank = banks.first;
    }

    if (!mounted) return;
    setState(() {
      _banks = finalBanks;
      _detail = detail;
      _selectedBank = selectedBank;
      _error = null;
      _loading = false;
    });
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
