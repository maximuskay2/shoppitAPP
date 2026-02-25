package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class RegistrationRequest(
    @SerializedName("email") val email : String,
    )
data class RegistrationResponse(
    val success: Boolean,
    val message: String,
    val data: RegistrationData?
)

data class RegistrationData(
    val token: String?
)

