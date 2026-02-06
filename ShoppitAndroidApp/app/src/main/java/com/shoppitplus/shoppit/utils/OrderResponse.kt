package com.shoppitplus.shoppit.utils

// OrderResponse.kt
data class OrderResponse(
    val success: Boolean,
    val message: String,
    val data: OrderDetail
)

data class OrderDetail(
    val id: String,
    var status: String,
    val tracking_id: String,
    val created_at: String,
    val gross_total_amount: Int,
    val delivery_fee: Int,
    val is_gift: Boolean,
    val receiver_name: String?,
    val receiver_delivery_address: String?,
    val receiver_phone: String?,
    val user: Customer,
    val line_items: List<LineItem>
)

data class Customer(
    val name: String,
    val address: String?,
    val phone: String?
)



// GenericResponse.kt (for status update)
data class GenericResponse(
    val success: Boolean,
    val message: String,
    val data: Any? = null
)