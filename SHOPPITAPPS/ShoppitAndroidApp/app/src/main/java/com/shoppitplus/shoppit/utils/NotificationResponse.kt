package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

// NotificationResponse.kt

data class NotificationResponse(
    val success: Boolean,
    val message: String,
    val data: NotificationDataWrapper
)

data class NotificationDataWrapper(
    val current_page: Int,
    @SerializedName("data")
    val data: List<NotificationItem>,
    val last_page: Int
)

data class NotificationItem(
    val id: String,
    val type: String,
    val notifiable_type: String,
    val notifiable_id: String,
    val data: NotificationDetail,
    val read_at: String?,
    val created_at: String,
    val updated_at: String
)

data class NotificationDetail(
    val order_id: String?,
    val tracking_id: String?,
    val customer_name: String?,
    val amount: Int?,
    val currency: String?,
    val items_count: Int?,
    val settlement_id: String? = null,
    val vendor_amount: Int? = null,
    val platform_fee: Int? = null,
    val wallet_balance: Int? = null
)


// For unread count
data class UnreadCountResponse(
    val success: Boolean,
    val message: String,
    val data: UnreadCountData
)

data class UnreadCountData(
    val unread: Int
)

// For single notification
data class SingleNotificationResponse(
    val success: Boolean,
    val message: String,
    val data: NotificationItem
)

// For mark as read
data class MarkReadResponse(
    val success: Boolean,
    val message: String,
    val data: Any? = null
)