package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class AddToCartRequest(
    @SerialName("product_id") val productId: String,
    @SerialName("quantity") val quantity: Int
)

@Serializable
data class AddToCartResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
)

@Serializable
data class CartResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: CartData? = null
)

@Serializable
data class CartData(
    @SerialName("vendors") val vendors: List<CartVendor>,
    @SerialName("total_amount") val totalAmount: Double = 0.0
)

@Serializable
data class CartVendor(
    @SerialName("vendor") val vendor: VendorDto,
    @SerialName("items") val items: List<CartItem>,
    @SerialName("subtotal") val subtotal: Double,
    @SerialName("delivery_fee") val deliveryFee: Double,
    @SerialName("vendor_total") val vendorTotal: Double,
    @SerialName("item_count") val itemCount: Int
)

@Serializable
data class CartItem(
    @SerialName("id") val id: String,
    @SerialName("product") val product: ProductDto,
    @SerialName("quantity") val quantity: Int,
    @SerialName("price") val price: Double,
    @SerialName("subtotal") val subtotal: Double
)

@Serializable
data class VendorCartResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: CartVendor? = null
)

@Serializable
data class UpdateCartItemRequest(
    @SerialName("quantity") val quantity: Int
)

@Serializable
data class ProcessCartRequest(
    @SerialName("vendor_id") val vendorId: String,
    @SerialName("order_notes") val orderNotes: String? = null,
    @SerialName("wallet_usage") val walletUsage: Int = 0,
    @SerialName("is_gift") val isGift: Int = 0,
    @SerialName("receiver_name") val receiverName: String? = null,
    @SerialName("receiver_email") val receiverEmail: String? = null,
    @SerialName("receiver_phone") val receiverPhone: String? = null,
    @SerialName("receiver_delivery_address") val receiverDeliveryAddress: String? = null,
    @SerialName("delivery_latitude") val deliveryLatitude: Double? = null,
    @SerialName("delivery_longitude") val deliveryLongitude: Double? = null
)

@Serializable
data class ProcessCartResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: ProcessCartData? = null
)

@Serializable
data class ProcessCartData(
    @SerialName("order_id") val orderId: String? = null,
    @SerialName("authorization_url") val authorizationUrl: String? = null,
    @SerialName("access_code") val accessCode: String? = null,
    @SerialName("reference") val reference: String? = null
)

@Serializable
data class DeliveryZoneCheckResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: DeliveryZoneData? = null
)

@Serializable
data class DeliveryZoneData(
    @SerialName("in_zone") val inZone: Boolean
)
