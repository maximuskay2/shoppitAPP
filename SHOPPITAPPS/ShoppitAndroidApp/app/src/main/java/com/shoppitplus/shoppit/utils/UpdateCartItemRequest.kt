package com.shoppitplus.shoppit.utils

data class UpdateCartItemRequest(val quantity: Int)

data class Order(
    val id: String,
    val user_id: String,
    val vendor_id: String,
    var status: String,
    val refund_status: String? = null,
    val refund_reason: String? = null,
    val refund_requested_at: String? = null,
    val refund_processed_at: String? = null,
    val email: String,
    val tracking_id: String,
    val order_notes: String?,
    val is_gift: Boolean,
    val receiver_delivery_address: String?,
    val receiver_name: String?,
    val receiver_email: String?,
    val receiver_phone: String?,
    val currency: String,
    val payment_reference: String,
    val processor_transaction_id: String?,
    val delivery_fee: Int,
    val gross_total_amount: Int,
    val net_total_amount: Int,
    val paid_at: String?,
    val dispatched_at: String?,
    val completed_at: String?,
    val settled_at: String?,
    val coupon_code: String?,
    val coupon_discount: Int,
    val created_at: String,
    val updated_at: String,
    val line_items: List<LineItem>,
    val vendor: Vendor,
    val user: User
)

data class LineItem(
    val id: String,
    val product: Product,
    val quantity: Int,
    val price: Int,
    val subtotal: Int,
    val type: String
)
data class User(
    val name: String,
    val email: String,
    val phone: String?,
    val avatar: String?,
    val type: String,
    val email_verified_at: String?,
    val kyc_status: String?,
    val username: String?,
    val address: String?,
    val address_2: String?,
    val city: String?,
    val state: String?,
    val country: String?,
    val created_at: String
)

