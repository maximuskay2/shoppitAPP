package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class OrdersResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: PaginatedResponse<OrderDetail>
)

@Serializable
data class OrderResponse(
    @SerialName("success")
    val success: Boolean,
    @SerialName("message")
    val message: String,
    @SerialName("data")
    val data: OrderDetail
)

@Serializable
data class OrderDetail(
    @SerialName("id")
    val id: String,
    @SerialName("status")
    var status: String,
    @SerialName("refund_status")
    val refundStatus: String? = null,
    @SerialName("tracking_id")
    val trackingId: String,
    @SerialName("created_at")
    val createdAt: String,
    @SerialName("gross_total_amount")
    val grossTotalAmount: Double,
    @SerialName("delivery_fee")
    val deliveryFee: Double,
    @SerialName("is_gift")
    val isGift: Boolean,
    @SerialName("receiver_name")
    val receiverName: String? = null,
    @SerialName("receiver_delivery_address")
    val receiverDeliveryAddress: String? = null,
    @SerialName("receiver_phone")
    val receiverPhone: String? = null,
    @SerialName("user")
    val user: Customer,
    @SerialName("line_items")
    val lineItems: List<LineItem>
)

@Serializable
data class Customer(
    @SerialName("name")
    val name: String,
    @SerialName("address")
    val address: String? = null,
    @SerialName("phone")
    val phone: String? = null
)

@Serializable
data class LineItem(
    @SerialName("id")
    val id: String,
    @SerialName("product_name")
    val productName: String? = null,
    @SerialName("product")
    val product: ProductDto? = null,
    @SerialName("quantity")
    val quantity: Int,
    @SerialName("price")
    val price: Double
)
