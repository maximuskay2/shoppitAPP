package com.shoppitplus.shoppit.utils

data class UserResponse(
    val success: Boolean,
    val message: String,
    val data: UserData?
)

data class UserData(
    val name: String,
    val email: String,
    val phone: String,
    val avatar: String?,
    val address: String?,
    val city: String?,
    val state: String?
)
