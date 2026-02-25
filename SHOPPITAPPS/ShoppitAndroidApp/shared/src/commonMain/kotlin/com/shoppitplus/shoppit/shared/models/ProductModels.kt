package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class ProductResponse(
    @SerialName("success") val success: Boolean = true,
    @SerialName("message") val message: String = "Success",
    @SerialName("data") val data: List<ProductDto>
)

@Serializable
data class ProductDto(
    @SerialName("id")
    val id: String? = null,
    @SerialName("name")
    val name: String,
    @SerialName("price")
    val price: Double,
    @SerialName("discount_price")
    val discountPrice: Double? = null,
    @SerialName("description")
    val description: String? = null,
    @SerialName("avatar")
    val avatar: List<ImageItem>? = null,
    @SerialName("is_available")
    val isAvailable: Boolean = true
)

@Serializable
data class ImageItem(
    @SerialName("secure_url")
    val secureUrl: String
)

@Serializable
data class VendorProductsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: List<ProductDto>? = null
)

@Serializable
data class ToggleAvailabilityRequest(
    @SerialName("is_available") val isAvailable: Boolean
)

@Serializable
data class CreateProductResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: ProductDto? = null
)

@Serializable
data class DeleteProductResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String
)

@Serializable
data class ProductCategoryResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: List<ProductCategory>
)

@Serializable
data class ProductCategory(
    @SerialName("id") val id: String,
    @SerialName("name") val name: String
)

@Serializable
data class CreateCategoryResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: ProductCategory? = null
)
