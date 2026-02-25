package com.shoppitplus.shoppit.shared.ui

import androidx.compose.ui.window.ComposeUIViewController

fun MainViewController() = ComposeUIViewController(
    configure = { enforceStrictPlistSanityCheck = false }
) {
    ShoppitApp()
}
