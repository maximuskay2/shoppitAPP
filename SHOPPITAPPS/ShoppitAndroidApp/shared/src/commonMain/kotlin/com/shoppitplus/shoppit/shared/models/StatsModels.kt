package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class StatsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: StatsData
)

@Serializable
data class StatsData(
    @SerialName("period") val period: Period,
    @SerialName("orders") val orders: OrderStats,
    @SerialName("revenue") val revenue: RevenueStats,
    @SerialName("currency") val currency: String
)

@Serializable
data class Period(
    @SerialName("month") val month: Int,
    @SerialName("year") val year: Int
)

@Serializable
data class OrderStats(
    @SerialName("total") val total: Int,
    @SerialName("pending") val pending: Int,
    @SerialName("processing") val processing: Int,
    @SerialName("paid") val paid: Int,
    @SerialName("dispatched") val dispatched: Int,
    @SerialName("completed") val completed: Int,
    @SerialName("cancelled") val cancelled: Int,
    @SerialName("refunded") val refunded: Int,
    @SerialName("failed") val failed: Int
)

@Serializable
data class RevenueStats(
    @SerialName("total_revenue") val totalRevenue: String,
    @SerialName("total_settlements") val totalSettlements: String,
    @SerialName("total_platform_fees") val totalPlatformFees: String,
    @SerialName("pending_settlement") val pendingSettlement: String,
    @SerialName("average_order_value") val averageOrderValue: String
)

@Serializable
data class VendorAnalyticsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String? = null,
    @SerialName("data") val data: VendorAnalyticsData? = null
)

@Serializable
data class VendorAnalyticsData(
    @SerialName("sales_trends") val salesTrends: List<SalesTrendItem>? = null,
    @SerialName("top_products") val topProducts: List<TopProductItem>? = null
)

@Serializable
data class SalesTrendItem(
    @SerialName("month") val month: String,
    @SerialName("orders") val orders: Int,
    @SerialName("revenue") val revenue: Double
)

@Serializable
data class TopProductItem(
    @SerialName("id") val id: String,
    @SerialName("name") val name: String,
    @SerialName("sales_count") val salesCount: Int = 0
)
