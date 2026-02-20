package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class DeliveryZoneCheckResponse(
    val success: Boolean,
    val message: String,
    val data: DeliveryZoneCheckData?
)

data class DeliveryZoneCheckData(
    @SerializedName("in_zone") val inZone: Boolean,
    val zone: DeliveryZoneInfo?
)

data class DeliveryZoneInfo(
    val id: Int,
    val name: String,
    @SerializedName("base_fee") val baseFee: Double,
    @SerializedName("per_km_fee") val perKmFee: Double,
    @SerializedName("min_order_amount") val minOrderAmount: Double
)
