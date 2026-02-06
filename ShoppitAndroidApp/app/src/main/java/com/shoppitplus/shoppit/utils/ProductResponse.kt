package com.shoppitplus.shoppit.utils

data class ProductResponse(
    val data: List<Products>
)

data class Products(
    val name: String,
    val price: Int,
    val discount_price: Int?,
    val avatar: List<ImageItem>? // <-- MUST be nullable
)

data class ImageItem(
    val secure_url: String
)

