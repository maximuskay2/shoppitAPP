package com.shoppitplus.shoppit.utils

data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: PaginatedResponse<T>  // ← Now matches API
)
data class ApiResponses<T>(
    val success: Boolean,
    val message: String,
    val data: T
)


data class ProductDto(
    val id: String,
    val vendor_id: String,
    val product_category_id: String,
    val name: String,
    val avatar: List<String>?,   // ✅ FIX
    val description: String?,
    val price: Int,
    val discount_price: Int?,
    val approximate_delivery_time: Int?,
    val is_available: Boolean,
    val created_at: String?,
    val updated_at: String?
)

data class VendorDto(
    val id: String,
    val name: String,
    val phone: String?,
    val avatar: String?,
    val address: String?,
    val address_2: String?,
    val city: String?,
    val state: String?,
    val country: String?,
    val opening_time: String?,
    val closing_time: String?,
    val is_open: Boolean,
    val approximate_shopping_time: String?,
    val delivery_fee: Int,
    val average_rating: Int
)

data class PaginatedResponse<T>(
    val data: List<T>,
    val next_cursor: String?,
    val prev_cursor: String?,
    val has_more: Boolean,
    val per_page: Int
)
data class ApiErrorResponse(
    val success: Boolean?,
    val message: String?
)
