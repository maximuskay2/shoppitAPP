package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Email
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.components.ShoppitTextField
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@Composable
fun RegisterScreen(
    onRegisterSuccess: (email: String) -> Unit,
    onNavigateToLogin: () -> Unit
) {
    var email by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }

    val scrollState = rememberScrollState()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // --- 3D DEPTH BACKGROUND ---
        Box(
            modifier = Modifier
                .size(350.dp)
                .offset(x = (-150).dp, y = 100.dp)
                .blur(100.dp)
                .clip(CircleShape)
                .background(ShoppitPrimary.copy(alpha = 0.15f))
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp)
                .verticalScroll(scrollState),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(60.dp))

            // Kinetic Headline
            KineticHeadline(
                text = "Create Account",
                scrollOffset = scrollState.value,
                threshold = 200
            )

            Text(
                text = "Join Shoppit and start shopping smart",
                style = MaterialTheme.typography.bodyMedium,
                color = ShoppitTextPrimary.copy(alpha = 0.6f),
                modifier = Modifier.padding(top = 8.dp)
            )

            Spacer(modifier = Modifier.height(48.dp))

            // Bento-Style Input Card
            BentoCard(
                modifier = Modifier.fillMaxWidth()
            ) {
                ShoppitTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = "Email Address",
                    placeholder = "e.g. name@example.com",
                    leadingIcon = { Icon(Icons.Default.Email, contentDescription = null, tint = ShoppitPrimary) }
                )
            }

            Spacer(modifier = Modifier.height(40.dp))

            ShoppitButton(
                text = "Continue",
                isLoading = isLoading,
                onClick = {
                    if (email.isNotBlank()) {
                        isLoading = true
                        // Logic integration
                    }
                },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Footer
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Already have an account?",
                    style = MaterialTheme.typography.bodyMedium,
                    color = ShoppitTextPrimary.copy(alpha = 0.7f)
                )
                TextButton(onClick = onNavigateToLogin) {
                    Text("Login", color = ShoppitPrimary, fontWeight = FontWeight.ExtraBold)
                }
            }
        }
    }
}
