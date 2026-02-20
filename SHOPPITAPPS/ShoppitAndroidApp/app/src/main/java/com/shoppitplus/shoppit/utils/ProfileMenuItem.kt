package com.shoppitplus.shoppit.utils

data class ProfileMenuItem(
    val iconRes: Int,
    val title: String,
    val navAction: Int? = null, // Use for navigation
    val isDestructive: Boolean = false // For Logout/Delete
)
