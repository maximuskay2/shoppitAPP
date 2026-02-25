package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Email
import androidx.compose.material.icons.filled.Lock
import androidx.compose.material.icons.filled.Visibility
import androidx.compose.material.icons.filled.VisibilityOff
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
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
import kotlinx.coroutines.delay

@Composable
fun LoginScreen(
    onLoginSuccess: (token: String, role: String) -> Unit,
    onNavigateToRegister: () -> Unit,
    onForgotPassword: () -> Unit
) {
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
    var rememberMe by remember { mutableStateOf(false) }
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
                .size(400.dp)
                .offset(x = 200.dp, y = (-100).dp)
                .blur(80.dp)
                .clip(CircleShape)
                .background(ShoppitPrimary.copy(alpha = 0.1f))
        )

        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(24.dp)
                .verticalScroll(scrollState),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Spacer(modifier = Modifier.height(60.dp))

            // Kinetic Headline Component
            KineticHeadline(
                text = "Welcome Back",
                scrollOffset = scrollState.value,
                threshold = 200
            )

            Text(
                text = "Login to continue your shopping journey",
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
                        label = "Email Address",
                        placeholder = "Enter your email",
                        leadingIcon = { Icon(Icons.Default.Email, contentDescription = null, tint = ShoppitPrimary) }
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

                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Row(verticalAlignment = Alignment.CenterVertically) {
                            Checkbox(
                                checked = rememberMe,
                                onCheckedChange = { rememberMe = it },
                                colors = CheckboxDefaults.colors(checkedColor = ShoppitPrimary)
                            )
                            Text("Remember me", style = MaterialTheme.typography.bodySmall)
                        }
                        TextButton(onClick = onForgotPassword) {
                            Text("Forgot Password?", color = ShoppitPrimary, fontWeight = FontWeight.Bold)
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(40.dp))

            ShoppitButton(
                text = "Continue",
                isLoading = isLoading,
                onClick = {
                    isLoading = true
                    // Auth logic integrated via shared ApiClient later
                },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Footer
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "I don't have an account?",
                    style = MaterialTheme.typography.bodyMedium,
                    color = ShoppitTextPrimary.copy(alpha = 0.7f)
                )
                TextButton(onClick = onNavigateToRegister) {
                    Text("Register", color = ShoppitPrimary, fontWeight = FontWeight.ExtraBold)
                }
            }
        }
    }
}
