package com.shoppitplus.shoppit.utils

// VendorResponse.kt (data class)
data class VendorResponse(
    val success: Boolean,
    val message: String,
    val data: VendorData
)

data class VendorData(
    val name: String,
    val business_name: String,
    val email: String,
    val phone: String,
    val avatar: String,
    val type: String,
    val email_verified_at: String,
    val kyb_status: String,
    val username: String?,
    val address: String,
    val address_2: String?,
    val city: String,
    val state: String,
    val country: String,
    val opening_time: String,
    val closing_time: String,
    val approximate_shopping_time: String,
    val delivery_fee: Int,
    val created_at: String
)
// StatsResponse.kt (data class)
data class StatsResponse(
    val success: Boolean,
    val message: String,
    val data: StatsData
)

data class StatsData(
    val period: Period,
    val orders: Orders,
    val revenue: Revenue,
    val currency: String
)

data class Period(
    val month: Int,
    val year: Int
)

data class Orders(
    val total: Int,
    val pending: Int,
    val processing: Int,
    val paid: Int,
    val dispatched: Int,
    val completed: Int,
    val cancelled: Int,
    val refunded: Int,
    val failed: Int
)

data class Revenue(
    val total_revenue: String,
    val total_settlements: String,
    val total_platform_fees: String,
    val pending_settlement: String,
    val average_order_value: String
)