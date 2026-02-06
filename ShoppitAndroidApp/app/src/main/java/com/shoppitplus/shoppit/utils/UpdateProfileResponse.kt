package com.shoppitplus.shoppit.utils

data class UpdateProfileResponse(
    val success: Boolean,
    val message: String,
    val data: UpdateProfileData
)

data class UpdateProfileData(
    val user: User
)