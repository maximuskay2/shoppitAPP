package com.shoppitplus.shoppit.shared.models

import kotlinx.serialization.Serializable
import kotlinx.serialization.SerialName

@Serializable
data class NearbyVendorsResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: NearbyVendorsData
)

@Serializable
data class NearbyVendorsData(
    @SerialName("data") val data: List<VendorDto>,
    @SerialName("has_more") val hasMore: Boolean
)

@Serializable
data class VendorDto(
    @SerialName("id") val id: String,
    @SerialName("name") val name: String,
    @SerialName("business_name") val businessName: String? = null,
    @SerialName("avatar") val avatar: String? = null,
    @SerialName("address") val address: String? = null,
    @SerialName("state") val state: String? = null,
    @SerialName("city") val city: String? = null,
    @SerialName("is_open") val isOpen: Boolean = true,
    @SerialName("delivery_fee") val deliveryFee: Double = 0.0,
    @SerialName("average_rating") val averageRating: Double = 0.0
)

@Serializable
data class VendorResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: VendorData
)

@Serializable
data class VendorData(
    @SerialName("name") val name: String,
    @SerialName("business_name") val businessName: String,
    @SerialName("avatar") val avatar: String,
    @SerialName("opening_time") val openingTime: String,
    @SerialName("closing_time") val closingTime: String
)

@Serializable
data class StoreHoursRequest(
    @SerialName("opening_time") val openingTime: String,
    @SerialName("closing_time") val closingTime: String
)

@Serializable
data class StoreHoursResponse(
    @SerialName("success") val success: Boolean,
    @SerialName("message") val message: String,
    @SerialName("data") val data: StoreHoursData? = null
)

@Serializable
data class StoreHoursData(
    @SerialName("opening_time") val openingTime: String? = null,
    @SerialName("closing_time") val closingTime: String? = null
)
