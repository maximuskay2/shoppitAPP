package com.shoppitplus.shoppit.shared.ui

import androidx.compose.runtime.*
import com.shoppitplus.shoppit.shared.ui.screens.*
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTheme

enum class Screen {
    Splash, Login, Register, Home
}

@Composable
fun ShoppitApp() {
    var currentScreen by remember { mutableStateOf(Screen.Splash) }

    ShoppitTheme {
        when (currentScreen) {
            Screen.Splash -> SplashScreen(onNavigate = { currentScreen = Screen.Login })
            Screen.Login -> LoginScreen(
                onLoginSuccess = { _, _ -> currentScreen = Screen.Home },
                onNavigateToRegister = { currentScreen = Screen.Register },
                onForgotPassword = { }
            )
            Screen.Register -> RegisterScreen(
                onRegisterSuccess = { currentScreen = Screen.Login },
                onNavigateToLogin = { currentScreen = Screen.Login }
            )
            Screen.Home -> HomeScreen(
                vendors = emptyList(),
                products = emptyList(),
                onProductClick = {},
                onVendorClick = {},
                onSearchClick = {},
                onNotificationsClick = {}
            )
        }
    }
}
