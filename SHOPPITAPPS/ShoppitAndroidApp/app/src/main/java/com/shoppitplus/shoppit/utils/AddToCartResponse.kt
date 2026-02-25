package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class AddToCartRequest(
    @SerializedName("product_id")
    val product_id: String,

    @SerializedName("quantity")
    val quantity: Int
)

data class AddToCartResponse(
    @SerializedName("success")
    val success: Boolean,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: Any? = null
)