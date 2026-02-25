package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class BaseResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String
)
