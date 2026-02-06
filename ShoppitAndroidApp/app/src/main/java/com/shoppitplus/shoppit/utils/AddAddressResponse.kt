package com.shoppitplus.shoppit.utils

data class AddAddressResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)
data class AddAddressRequest(
    val address: String,
    val address_2: String? = null,
    val city: String,
    val state: String,
    val is_default: Int = 1
)