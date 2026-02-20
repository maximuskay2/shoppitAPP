package com.shoppitplus.shoppit.utils

data class StoreHoursRequest(
    val opening_time: String,
    val closing_time: String
)

data class StoreHoursResponse(
    val success: Boolean,
    val message: String,
    val data: StoreHoursData?
)

data class StoreHoursData(
    val opening_time: String?,
    val closing_time: String?
)
