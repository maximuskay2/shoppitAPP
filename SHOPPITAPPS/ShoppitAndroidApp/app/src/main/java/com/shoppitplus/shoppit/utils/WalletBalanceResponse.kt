package com.shoppitplus.shoppit.utils

// Request for deposit
data class DepositRequest(
    val amount: Int
)

// Wallet balance
data class WalletBalanceResponse(
    val success: Boolean,
    val message: String,
    val data: WalletBalanceData
)

data class WalletBalanceData(
    val balance: Double
)

// Deposit response
data class DepositResponse(
    val success: Boolean,
    val message: String,
    val data: DepositData?
)

data class DepositData(
    val authorization_url: String
)

// Transactions response
data class WalletTransactionsResponse(
    val success: Boolean,
    val message: String,
    val data: WalletTransactionsData
)

data class WalletTransactionsData(
    val `in`: Double,
    val `out`: Double,
    val data: Map<String, List<WalletTransactionItem>>  // Key: date like "December 27"
)

data class WalletTransactionItem(
    val type: String,          // e.g. "FUND_WALLET"
    val amount: Double,
    val currency: String,
    val status: String,        // "SUCCESSFUL"
    val reference: String,
    val description: String,
    val narration: String,
    val date: String,          // Full date time
    val payload: Any?,
    val fee: Double
)
data class WalletTransaction(
    val dateHeader: String,     // "December 27"
    val amount: Double,
    val type: String,
    val status: String,
    val time: String,           // e.g. "4:29:54 AM"
    val narration: String,
    val fee: Double?
)