package com.shoppitplus.shoppit.utils

import com.google.gson.annotations.SerializedName

data class CartResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String,
    @SerializedName("data") val data: CartData?
)

data class CartData(
    @SerializedName("id") val id: String,
    @SerializedName("user_id") val userId: String,
    @SerializedName("vendors") val vendors: List<CartVendor>,
    @SerializedName("subtotal") val subtotal: Int,
    @SerializedName("total_discount") val totalDiscount: Int,
    @SerializedName("cart_total") val cartTotal: Int,
    @SerializedName("total_items") val totalItems: Int,
    @SerializedName("vendor_count") val vendorCount: Int
)

data class CartVendor(
    @SerializedName("id") val id: String,
    @SerializedName("vendor") val vendor: Vendor,
    @SerializedName("items") val items: List<CartItem>,
    @SerializedName("subtotal") val subtotal: Int,
    @SerializedName("coupon") val coupon: Coupon?,
    @SerializedName("vendor_total") val vendorTotal: Int,
    @SerializedName("item_count") val itemCount: Int
)

data class Vendor(
    @SerializedName("id") val id: String,
    @SerializedName("name") val name: String,
    @SerializedName("phone") val phone: String,
    @SerializedName("avatar") val avatar: String?,
    @SerializedName("address") val address: String,
    @SerializedName("city") val city: String,
    @SerializedName("state") val state: String,
    @SerializedName("delivery_fee") val deliveryFee: Int,
    @SerializedName("is_open") val isOpen: Boolean,
    @SerializedName("approximate_shopping_time") val approximateShoppingTime: String,
    @SerializedName("average_rating") val averageRating: Double,
    val business_name: String,
    val username: String?,
    val country: String?,
    val opening_time: String?,
    val closing_time: String?,
    val created_at: String

)

data class CartItem(
    @SerializedName("id") val id: String,
    @SerializedName("product") val product: Product,
    @SerializedName("quantity") val quantity: Int,
    @SerializedName("price") val price: Int,
    @SerializedName("subtotal") val subtotal: Int
)

data class Coupon(
    @SerializedName("id") val id: String,
    @SerializedName("code") val code: String,
    @SerializedName("discount") val discount: Int
)



data class ClearCartResponse(
    @SerializedName("success") val success: Boolean,
    @SerializedName("message") val message: String,
    @SerializedName("data") val data: Any?
)