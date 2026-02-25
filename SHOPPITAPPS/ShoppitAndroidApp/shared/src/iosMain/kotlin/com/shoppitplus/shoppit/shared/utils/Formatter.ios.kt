package com.shoppitplus.shoppit.shared.utils

import platform.Foundation.NSNumber
import platform.Foundation.NSNumberFormatter
import platform.Foundation.NSNumberFormatterDecimalStyle

actual fun formatCurrency(amount: Double): String {
    val formatter = NSNumberFormatter()
    formatter.numberStyle = NSNumberFormatterDecimalStyle
    formatter.minimumFractionDigits = 0uL
    formatter.maximumFractionDigits = 0uL
    return formatter.stringFromNumber(NSNumber(amount)) ?: amount.toString()
}

actual fun formatDecimal(amount: Double, decimals: Int): String {
    val formatter = NSNumberFormatter()
    formatter.numberStyle = NSNumberFormatterDecimalStyle
    formatter.minimumFractionDigits = decimals.toULong()
    formatter.maximumFractionDigits = decimals.toULong()
    return formatter.stringFromNumber(NSNumber(amount)) ?: amount.toString()
}
