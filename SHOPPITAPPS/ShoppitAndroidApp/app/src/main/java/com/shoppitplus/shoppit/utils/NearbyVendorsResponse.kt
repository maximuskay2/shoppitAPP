package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class NearbyVendorsResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String,
    @SerializedName("data") val data: NearbyVendorsData
)

data class NearbyVendorsData(
    @SerializedName("data") val data: List<Vendor>,
    @SerializedName("next_cursor") val nextCursor: String?,
    @SerializedName("prev_cursor") val prevCursor: String?,
    @SerializedName("has_more") val hasMore: Boolean,
    @SerializedName("per_page") val perPage: Int
)

