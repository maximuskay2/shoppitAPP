class BankInfo {
  const BankInfo({required this.name, required this.code});

  final String name;
  final String code;

  factory BankInfo.fromJson(Map<String, dynamic> json) {
    return BankInfo(
      name: (json["name"] ?? "").toString(),
      code: (json["code"] ?? "").toString(),
    );
  }
}

class DriverPaymentDetail {
  const DriverPaymentDetail({
    required this.bankName,
    required this.bankCode,
    required this.accountNumber,
    required this.accountName,
  });

  final String bankName;
  final String bankCode;
  final String accountNumber;
  final String accountName;

  factory DriverPaymentDetail.fromJson(Map<String, dynamic> json) {
    return DriverPaymentDetail(
      bankName: (json["bank_name"] ?? "").toString(),
      bankCode: (json["bank_code"] ?? "").toString(),
      accountNumber: (json["account_number"] ?? "").toString(),
      accountName: (json["account_name"] ?? "").toString(),
    );
  }
}
