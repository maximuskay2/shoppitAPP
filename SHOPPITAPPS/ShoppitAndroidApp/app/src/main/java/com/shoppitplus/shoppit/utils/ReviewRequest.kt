package com.shoppitplus.shoppit.utils

data class ReviewRequest(
    val rating: Int,
    val comment: String?,
    val reviewable_type: String,
    val reviewable_id: String
)

data class ReviewResponse(
    val success: Boolean,
    val message: String
)
