package com.shoppitplus.shoppit.utils

data class OrderTrackingResponse(
    val success: Boolean,
    val message: String,
    val data: OrderTrackingData?
)

data class OrderTrackingData(
    val order_id: String,
    val status: String,
    val driver_id: String?,
    val driver: DriverSummary?,
    val driver_location: DriverLocationPoint?,
    val delivery_location: LocationPoint?,
    val updated_at: String
)

data class DriverSummary(
    val id: String?,
    val name: String?,
    val avatar: String?
)

data class DriverLocationPoint(
    val lat: Double?,
    val lng: Double?,
    val bearing: Double?,
    val speed: Double?,
    val accuracy: Double?,
    val recorded_at: String?
)

data class LocationPoint(
    val lat: Double?,
    val lng: Double?
)

data class OrderEtaResponse(
    val success: Boolean,
    val message: String,
    val data: OrderEtaData?
)

data class OrderEtaData(
    val order_id: String,
    val status: String,
    val eta_minutes: Int?,
    val updated_at: String
)
