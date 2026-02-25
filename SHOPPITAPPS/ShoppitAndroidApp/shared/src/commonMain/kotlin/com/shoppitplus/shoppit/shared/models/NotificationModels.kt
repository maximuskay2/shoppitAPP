package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class UnreadCountResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String? = null,
    @SerialName("data") val data: UnreadCountData? = null
)

@Serializable
data class UnreadCountData(
    @SerialName("unread") val unread: Int
)

@Serializable
data class NotificationResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: NotificationDataWrapper
)

@Serializable
data class NotificationDataWrapper(
    @SerialName("current_page") val currentPage: Int,
    @SerialName("data") val data: List<NotificationItem>? = null,
    @SerialName("last_page") val lastPage: Int
)

@Serializable
data class NotificationItem(
    @SerialName("id") val id: String,
    @SerialName("type") val type: String,
    @SerialName("data") val data: NotificationDetail,
    @SerialName("read_at") val readAt: String? = null,
    @SerialName("created_at") val createdAt: String
)

@Serializable
data class NotificationDetail(
    @SerialName("order_id") val orderId: String? = null,
    @SerialName("tracking_id") val trackingId: String? = null,
    @SerialName("customer_name") val customerName: String? = null,
    @SerialName("amount") val amount: Double? = null,
    @SerialName("vendor_amount") val vendorAmount: Double? = null,
    @SerialName("wallet_balance") val walletBalance: Double? = null
)

@Serializable
data class MarkReadResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String
)
