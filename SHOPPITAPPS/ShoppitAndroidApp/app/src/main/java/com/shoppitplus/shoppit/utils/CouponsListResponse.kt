package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class CouponsListResponse(
    val success: Boolean,
    val message: String? = null,
    val data: List<VendorCouponDto>? = null
)

data class VendorCouponDto(
    val uuid: String,
    val code: String,
    val description: String? = null,
    @SerializedName("discount_type") val discountType: String = "percentage",
    @SerializedName("discount_value") val discountValue: Double = 0.0,
    @SerializedName("min_order_amount") val minOrderAmount: Double? = null,
    @SerializedName("max_discount") val maxDiscount: Double? = null,
    @SerializedName("usage_limit") val usageLimit: Int? = null,
    @SerializedName("used_count") val usedCount: Int = 0,
    @SerializedName("start_date") val startDate: String? = null,
    @SerializedName("end_date") val endDate: String? = null,
    @SerializedName("is_active") val isActive: Boolean = true
)
