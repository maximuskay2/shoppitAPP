package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class LoginResponse(
    @SerializedName("success")
    val success: Boolean,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: LoginData?
)

data class LoginData(
    @SerializedName("token")
    val token: String,

    @SerializedName("role")
    val role: String  // "vendor" or "user"
)

data class LoginRequest(
    @SerializedName("email")
    val email: String,

    @SerializedName("password")
    val password: String
)