package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class WalletBalanceResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: WalletBalanceData
)

@Serializable
data class WalletBalanceData(
    @SerialName("balance") val balance: Double
)

@Serializable
data class DepositRequest(
    @SerialName("amount") val amount: Int
)

@Serializable
data class DepositResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: DepositData? = null
)

@Serializable
data class DepositData(
    @SerialName("authorization_url") val authorizationUrl: String
)

@Serializable
data class WalletTransactionsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: WalletTransactionsData
)

@Serializable
data class WalletTransactionsData(
    @SerialName("in") val income: Double,
    @SerialName("out") val expense: Double,
    @SerialName("data") val data: Map<String, List<WalletTransactionItem>>
)

@Serializable
data class WalletTransactionItem(
    @SerialName("type") val type: String,
    @SerialName("amount") val amount: Double,
    @SerialName("currency") val currency: String,
    @SerialName("status") val status: String,
    @SerialName("reference") val reference: String,
    @SerialName("description") val description: String,
    @SerialName("narration") val narration: String,
    @SerialName("date") val date: String,
    @SerialName("fee") val fee: Double
)

@Serializable
data class WalletTransaction(
    val dateHeader: String,
    val amount: Double,
    val type: String,
    val status: String,
    val time: String,
    val narration: String,
    val fee: Double?
)

@Serializable
data class VendorPayoutsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: VendorPayoutsData? = null
)

@Serializable
data class VendorPayoutsData(
    @SerialName("data") val data: List<VendorPayoutItem>
)

@Serializable
data class VendorPayoutItem(
    @SerialName("id") val id: String,
    @SerialName("total_amount") val totalAmount: Double? = null,
    @SerialName("platform_fee") val platformFee: Double? = null,
    @SerialName("vendor_amount") val vendorAmount: Double? = null,
    @SerialName("payment_gateway") val paymentGateway: String? = null,
    @SerialName("status") val status: String,
    @SerialName("settled_at") val settledAt: String? = null,
    @SerialName("currency") val currency: String? = null
)

@Serializable
data class VendorPayoutRequest(
    @SerialName("amount") val amount: Double
)

@Serializable
data class VendorPayoutRequestResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: VendorPayoutRequestData? = null
)

@Serializable
data class VendorPayoutRequestData(
    @SerialName("amount") val amount: Double? = null,
    @SerialName("status") val status: String? = null
)
