package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Store
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.components.ShoppitTextField
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@Composable
fun VendorLoginScreen(
    onLoginSuccess: (token: String, role: String) -> Unit,
    onNavigateToRegister: () -> Unit,
    onForgotPassword: () -> Unit
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    var isLoading by remember { mutableStateOf(false) }

    val scrollState = rememberScrollState()

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // --- 3D DEPTH BACKGROUND (Distinct color for Vendor) ---
        Box(
            modifier = Modifier
                .size(400.dp)
                .offset(x = (-200).dp, y = 300.dp)
                .blur(100.dp)
                .clip(CircleShape)
                .background(Color(0xFF1565C0).copy(alpha = 0.1f)) // Subtle Blue for Vendor
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
                text = "Vendor Login",
                scrollOffset = scrollState.value,
                threshold = 200
            )

            Text(
                text = "Manage your store and fulfill orders",
                style = MaterialTheme.typography.bodyMedium,
                color = ShoppitTextPrimary.copy(alpha = 0.6f),
                modifier = Modifier.padding(top = 8.dp)
            )

            Spacer(modifier = Modifier.height(48.dp))

            // Bento-Style Input Card
            BentoCard(
                modifier = Modifier.fillMaxWidth()
            ) {
                Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
                    ShoppitTextField(
                        value = email,
                        onValueChange = { email = it },
                        label = "Business Email",
                        placeholder = "Enter your email",
                        leadingIcon = { Icon(Icons.Default.Store, contentDescription = null, tint = ShoppitPrimary) }
                    )

                    ShoppitTextField(
                        value = password,
                        onValueChange = { password = it },
                        label = "Password",
                        placeholder = "Enter your password",
                        visualTransformation = if (passwordVisible) VisualTransformation.None else PasswordVisualTransformation(),
                        leadingIcon = { Icon(Icons.Default.Lock, contentDescription = null, tint = ShoppitPrimary) },
                        trailingIcon = {
                            IconButton(onClick = { passwordVisible = !passwordVisible }) {
                                Icon(
                                    imageVector = if (passwordVisible) Icons.Default.Visibility else Icons.Default.VisibilityOff,
                                    contentDescription = null
                                )
                            }
                        }
                    )

                    Box(modifier = Modifier.fillMaxWidth(), contentAlignment = Alignment.CenterEnd) {
                        TextButton(onClick = onForgotPassword) {
                            Text("Forgot Password?", color = ShoppitPrimary, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(40.dp))

            ShoppitButton(
                text = "Access Dashboard",
                isLoading = isLoading,
                onClick = {
                    isLoading = true
                    // Auth logic via shared ApiClient
                },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Footer
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Want to partner with us?",
                    style = MaterialTheme.typography.bodyMedium,
                    color = ShoppitTextPrimary.copy(alpha = 0.7f)
                )
                TextButton(onClick = onNavigateToRegister) {
                    Text("Register Store", color = ShoppitPrimary, fontWeight = FontWeight.ExtraBold)
                }
            }
        }
    }
}
