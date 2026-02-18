package com.shoppitplus.shoppit.utils

data class RefundStatusResponse(
    val success: Boolean,
    val message: String,
    val data: RefundStatusData?
)

data class RefundStatusData(
    val refund_status: String?,
    val refund_reason: String?,
    val refund_requested_at: String?,
    val refund_processed_at: String?
)
