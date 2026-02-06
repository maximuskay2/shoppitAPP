package com.shoppitplus.shoppit.utils



import com.google.gson.annotations.SerializedName

data class SetupProfileResponse(

    @SerializedName("success")
    val success: Boolean,

    @SerializedName("message")
    val message: String,

    @SerializedName("data")
    val data: Any?
)

data class SetupProfileRequest(

    @SerializedName("full_name")
    val fullName: String,

    @SerializedName("phone")
    val phone: String,

    @SerializedName("state")
    val state: String,

    @SerializedName("city")
    val city: String,

    @SerializedName("address")
    val address: String,

    @SerializedName("address_2")
    val address2: String? = null
)
