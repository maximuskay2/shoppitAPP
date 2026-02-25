package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class UserResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: UserData? = null
)

@Serializable
data class UserData(
    @SerialName("name") val name: String,
    @SerialName("email") val email: String,
    @SerialName("phone") val phone: String? = null,
    @SerialName("avatar") val avatar: String? = null,
    @SerialName("address") val address: String? = null,
    @SerialName("city") val city: String? = null,
    @SerialName("state") val state: String? = null
)

@Serializable
data class UpdateProfileRequest(
    @SerialName("full_name") val fullName: String,
    @SerialName("phone") val phone: String,
    @SerialName("email") val email: String
)

@Serializable
data class UpdateProfileResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: UpdateProfileData? = null
)

@Serializable
data class UpdateProfileData(
    @SerialName("user") val user: UserData
)
