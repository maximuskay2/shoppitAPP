package com.shoppitplus.shoppit.utils

data class OrdersResponse(
    val success: Boolean,
    val data: OrdersData
)

data class OrdersData(
    val data: List<Order>
)






