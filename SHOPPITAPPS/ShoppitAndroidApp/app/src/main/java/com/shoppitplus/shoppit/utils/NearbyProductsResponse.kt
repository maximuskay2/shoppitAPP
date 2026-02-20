package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

// Main response wrapper
data class NearbyProductsResponse(
    @SerializedName("success")
    val success: Boolean,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: NearbyProductsData
)

// Inner "data" object with pagination and product list
data class NearbyProductsData(
    @SerializedName("data")
    val data: List<Product>,

    @SerializedName("next_cursor")
    val nextCursor: String?,

    @SerializedName("prev_cursor")
    val prevCursor: String?,

    @SerializedName("has_more")
    val hasMore: Boolean,

    @SerializedName("per_page")
    val perPage: Int
)

// Single product
data class Product(
    @SerializedName("id")
    val id: String,

    @SerializedName("vendor_id")
    val vendorId: String,

    @SerializedName("product_category_id")
    val productCategoryId: String,

    @SerializedName("name")
    val name: String,

    @SerializedName("avatar")
    val avatar: List<String>?,

    @SerializedName("description")
    val description: String?,

    @SerializedName("price")
    val price: Int,

    @SerializedName("discount_price")
    val discountPrice: Int?,

    @SerializedName("approximate_delivery_time")
    val approximateDeliveryTime: Int,

    @SerializedName("is_available")
    val isAvailable: Boolean,

    @SerializedName("created_at")
    val createdAt: String,

    @SerializedName("updated_at")
    val updatedAt: String
)