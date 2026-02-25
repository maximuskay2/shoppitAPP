package com.shoppitplus.shoppit.utils

data class VendorPayoutsResponse(
    val success: Boolean,
    val message: String,
    val data: VendorPayoutsData?
)

data class VendorPayoutsData(
    val data: List<VendorPayoutItem>
)

data class VendorPayoutItem(
    val id: String,
    val total_amount: Double?,
    val platform_fee: Double?,
    val vendor_amount: Double?,
    val payment_gateway: String?,
    val status: String,
    val settled_at: String?,
    val currency: String?
)

data class VendorPayoutRequest(
    val amount: Double
)

data class VendorPayoutRequestResponse(
    val success: Boolean,
    val message: String,
    val data: VendorPayoutRequestData?
)

data class VendorPayoutRequestData(
    val amount: Double?,
    val status: String?
)
