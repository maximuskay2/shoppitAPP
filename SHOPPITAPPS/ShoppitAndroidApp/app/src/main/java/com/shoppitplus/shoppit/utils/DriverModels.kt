package com.shoppitplus.shoppit.utils

data class DriverLoginRequest(
    val email: String,
    val password: String
)

data class DriverRegisterRequest(
    val name: String?,
    val email: String?,
    val phone: String?,
    val password: String?,
    val license_number: String?,
    val vehicle_type: String?
)

data class DriverAuthResponse(
    val success: Boolean,
    val message: String,
    val data: DriverAuthData?
)

data class DriverAuthData(
    val token: String?,
    val user: DriverUser?
)

data class DriverUser(
    val id: String?,
    val name: String?,
    val email: String?,
    val phone: String?,
    val driver: DriverProfile?
)

data class DriverProfile(
    val is_verified: Boolean?,
    val is_online: Boolean?,
    val vehicle_type: String?,
    val license_number: String?
)

data class DriverProfileResponse(
    val success: Boolean,
    val message: String,
    val data: DriverUser?
)

data class MoneyAmount(
    val amount: Double,
    val currency: String
)

data class DriverVendor(
    val id: String?,
    val business_name: String?,
    val latitude: Double?,
    val longitude: Double?,
    val delivery_fee: MoneyAmount?
)

data class DriverOrder(
    val id: String,
    val status: String,
    val vendor: DriverVendor?,
    val delivery_latitude: Double?,
    val delivery_longitude: Double?,
    val receiver_name: String?,
    val receiver_phone: String?,
    val otp_code: String?,
    val gross_total_amount: MoneyAmount?,
    val net_total_amount: MoneyAmount?,
    val created_at: String?
)

data class DriverOrdersResponse(
    val success: Boolean,
    val message: String,
    val data: PaginatedResponse<DriverOrder>
)

data class DriverOrderResponse(
    val success: Boolean,
    val message: String,
    val data: DriverOrder?
)

data class DriverRejectRequest(
    val reason: String?
)

data class DriverOtpRequest(
    val otp_code: String
)

data class DriverStatusRequest(
    val is_online: Boolean
)

data class DriverLocationUpdateRequest(
    val lat: Double,
    val lng: Double,
    val bearing: Double? = null,
    val speed: Double? = null,
    val accuracy: Double? = null
)

data class DriverEarningsSummary(
    val total_earnings: MoneyAmount?,
    val today_earnings: MoneyAmount?,
    val total_deliveries: Int?
)

data class DriverEarningsResponse(
    val success: Boolean,
    val message: String,
    val data: DriverEarningsSummary?
)

data class DriverStatsResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)

data class DriverPayoutRequest(
    val amount: Double? = null
)

data class DriverPayoutResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)

data class DriverSupportRequest(
    val subject: String,
    val message: String
)

data class DriverSupportResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)

data class DriverNavigationRequest(
    val origin_lat: Double,
    val origin_lng: Double,
    val destination_lat: Double,
    val destination_lng: Double
)

data class DriverVehiclesResponse(
    val success: Boolean,
    val message: String,
    val data: Any?
)
