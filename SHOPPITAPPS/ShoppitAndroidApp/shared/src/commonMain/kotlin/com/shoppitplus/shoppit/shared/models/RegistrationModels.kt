package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class RegistrationRequest(
    @SerialName("email") val email: String
)

@Serializable
data class RegistrationResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: RegistrationData? = null
)

@Serializable
data class RegistrationData(
    @SerialName("token") val token: String? = null
)
