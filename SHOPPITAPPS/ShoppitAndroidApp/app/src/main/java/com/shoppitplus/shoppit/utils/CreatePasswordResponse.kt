package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class CreatePasswordResponse(
    @SerializedName("success")
    val success: Boolean,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: Any?
)
data class CreatePasswordRequest(
    @SerializedName("password")
    val password: String
)