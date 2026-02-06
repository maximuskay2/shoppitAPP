package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName


data class VendorProductsResponse(
    val success: Boolean,
    val message: String,
    @SerializedName("data")           // ← this is the correct field name
    val data: List<Product> = emptyList())

data class DeleteProductResponse(
    val success: Boolean,
    val message: String,
    val data: Any? // null
)

data class UpdateProductResponse(
    val success: Boolean,
    val message: String,
    val data: ProductDto?
)


// Request body for simple toggle (if your backend supports JSON patch for this)
data class ToggleAvailabilityRequest(
    val is_available: Boolean
)