package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class LoginResponse(
    @SerialName("success")
    val success: Boolean,
    @SerialName("message")
    val message: String,
    @SerialName("data")
    val data: LoginData? = null
)

@Serializable
data class LoginData(
    @SerialName("token")
    val token: String,
    @SerialName("role")
    val role: String // "vendor" or "user"
)

@Serializable
data class LoginRequest(
    @SerialName("email")
    val email: String,
    @SerialName("password")
    val password: String
)

@Serializable
data class ResetCodeRequest(
    @SerialName("email")
    val email: String
)

@Serializable
data class VerifyCodeRequest(
    @SerialName("email")
    val email: String,
    @SerialName("verification_code")
    val verificationCode: String
)

@Serializable
data class ApiErrorResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String
)

@Serializable
data class ResetPasswordRequest(
    @SerialName("email")
    val email: String,
    @SerialName("password")
    val password: String,
    @SerialName("password_confirmation")
    val passwordConfirmation: String
)
