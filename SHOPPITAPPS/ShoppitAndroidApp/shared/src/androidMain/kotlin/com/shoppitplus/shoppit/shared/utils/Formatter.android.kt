package com.shoppitplus.shoppit.shared.utils

actual fun formatCurrency(amount: Double): String {
    return "%,.0f".format(amount)
}

actual fun formatDecimal(amount: Double, decimals: Int): String {
    return "%,.${decimals}f".format(amount)
}
