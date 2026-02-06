package com.shoppitplus.shoppit.onboarding

data class PromoSlide(
    val title: String,
    val description: String,
    val imageRes: Int,
    val showButton: Boolean = true,
    val buttonText: String = "Shop Now" // default if not provided

)