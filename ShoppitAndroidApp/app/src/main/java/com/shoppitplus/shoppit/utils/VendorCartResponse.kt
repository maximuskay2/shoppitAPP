package com.shoppitplus.shoppit.utils

data class VendorCartResponse(
    val success: Boolean,
    val message: String,
    val data: VendorCartData
)

data class VendorCartData(
    val id: String,
    val vendor: Vendor,
    val items: List<CartItemDetail>,
    val subtotal: Int,
    val delivery_fee: Int,
    val vendor_total: Int,
    val item_count: Int
)
data class CartItemDetail(
    val id: String,
    val product: Product,
    val quantity: Int,
    val price: Int,
    val subtotal: Int
)

data class DeleteCartItemResponse(
    val success: Boolean,
    val message: String,
    val data: Any? = null
)
data class ProcessCartResponse(
    val success: Boolean,
    val message: String,
    val data: PaymentData
)

data class PaymentData(
    val payment_reference: String,
    val amount: Int,
    val gross_total: Int,
    val coupon_discount: Int?,
    val net_total: Int,
    val authorization_url: String,
    val coupon: CouponData?
)

data class CouponData(
    val code: String,
    val discount: Int
)

// Request for processing payment
data class ProcessCartRequest(
    val vendor_id: String,
    val order_notes: String = "",
    val wallet_usage: Int = 0,
    val is_gift: Int = 0,
    val receiver_name: String? = null,
    val receiver_email: String? = null,
    val receiver_phone: String? = null,
    val receiver_delivery_address: String? = null
)