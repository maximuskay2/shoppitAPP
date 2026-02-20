package com.shoppitplus.shoppit.utils

data class CreateProductResponse(
    val success: Boolean,
    val message: String,
    val data: Product
)

data class ProductCategoryResponse(
    val success: Boolean,
    val message: String,
    val data: List<ProductCategory>
)

data class CreateCategoryResponse(
    val success: Boolean,
    val message: String,
    val data: ProductCategory
)

data class ProductCategory(
    val id: String,
    val name: String,
    val avatar: String?,
    val description: String?,
    val is_active: Boolean
)

