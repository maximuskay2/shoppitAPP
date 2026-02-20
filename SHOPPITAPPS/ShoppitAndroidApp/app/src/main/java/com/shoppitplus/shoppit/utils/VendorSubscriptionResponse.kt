package com.shoppitplus.shoppit.utils

data class VendorSubscriptionResponse(
    val success: Boolean,
    val message: String,
    val data: VendorSubscription
)

data class VendorSubscription(
    val status: String,
    val ends_at: String,
    val plan: VendorPlan
)

data class VendorPlan(
    val key: Int,
    val name: String,
    val features: List<String>
)
