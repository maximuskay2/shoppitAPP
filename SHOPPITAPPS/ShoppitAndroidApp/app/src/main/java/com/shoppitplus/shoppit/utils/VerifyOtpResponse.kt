package com.shoppitplus.shoppit.utils

data class VerifyOtpResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)



data class VerifyOtpRequest(
    val email: String,
    val verification_code: String
)
data class ResendOtpRequest(
    val email: String
)
