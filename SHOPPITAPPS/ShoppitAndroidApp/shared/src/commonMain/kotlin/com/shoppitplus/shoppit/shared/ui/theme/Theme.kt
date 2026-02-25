package com.shoppitplus.shoppit.shared.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.runtime.Composable

private val LightColorScheme = lightColorScheme(
    primary = ShoppitPrimary,
    onPrimary = androidx.compose.ui.graphics.Color.White,
    primaryContainer = ShoppitPrimaryLight,
    background = ShoppitBackground,
    surface = androidx.compose.ui.graphics.Color.White,
    error = ShoppitError
)

@Composable
fun ShoppitTheme(
    darkTheme: Boolean = isSystemInDarkTheme(),
    content: @Composable () -> Unit
) {
    val colorScheme = if (darkTheme) {
        // Fallback to light for now as per established brand
        LightColorScheme
    } else {
        LightColorScheme
    }

    MaterialTheme(
        colorScheme = colorScheme,
        typography = ShoppitTypography,
        content = content
    )
}
