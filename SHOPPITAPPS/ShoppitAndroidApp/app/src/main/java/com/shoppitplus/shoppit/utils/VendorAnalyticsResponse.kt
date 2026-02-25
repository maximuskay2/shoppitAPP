package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class VendorAnalyticsResponse(
    val success: Boolean,
    val message: String? = null,
    val data: VendorAnalyticsData? = null
)

data class VendorAnalyticsData(
    @SerializedName("sales_trends") val salesTrends: List<SalesTrendItem>? = null,
    @SerializedName("top_products") val topProducts: List<TopProductItem>? = null
)

data class SalesTrendItem(
    val month: String,
    val orders: Int,
    val revenue: Double
)

data class TopProductItem(
    val id: String,
    val name: String,
    @SerializedName("sales_count") val salesCount: Int = 0
)
