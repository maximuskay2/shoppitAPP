package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
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

enum class ForgotPasswordStep {
    SEND_CODE, VERIFY_CODE, RESET_PASSWORD
}

@OptIn(ExperimentalAnimationApi::class)
@Composable
fun ForgotPasswordScreen(
    initialEmail: String = "",
    onBack: () -> Unit,
    onSuccess: () -> Unit
) {
    var step by remember { mutableStateOf(ForgotPasswordStep.SEND_CODE) }
    var email by remember { mutableStateOf(initialEmail) }
    var code by remember { mutableStateOf("") }
    var newPassword by remember { mutableStateOf("") }
    var confirmPassword by remember { mutableStateOf("") }
    var passwordVisible by remember { mutableStateOf(false) }
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
                .size(300.dp)
                .offset(x = (-100).dp, y = (-50).dp)
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
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically
            ) {
                IconButton(onClick = onBack) {
                    Icon(Icons.Default.ArrowBack, contentDescription = "Back", tint = ShoppitTextPrimary)
                }
            }

            Spacer(modifier = Modifier.height(20.dp))

            // Kinetic Headline
            KineticHeadline(
                text = when(step) {
                    ForgotPasswordStep.SEND_CODE -> "Forgot Password"
                    ForgotPasswordStep.VERIFY_CODE -> "Verify Code"
                    ForgotPasswordStep.RESET_PASSWORD -> "New Password"
                },
                scrollOffset = scrollState.value,
                threshold = 200
            )

            Text(
                text = when(step) {
                    ForgotPasswordStep.SEND_CODE -> "Enter your email to receive a reset code"
                    ForgotPasswordStep.VERIFY_CODE -> "Enter the code sent to $email"
                    ForgotPasswordStep.RESET_PASSWORD -> "Set a strong new password for your account"
                },
                style = MaterialTheme.typography.bodyMedium,
                color = ShoppitTextPrimary.copy(alpha = 0.6f),
                modifier = Modifier.padding(top = 8.dp)
            )

            Spacer(modifier = Modifier.height(48.dp))

            // Bento-Style Input Card
            BentoCard(
                modifier = Modifier.fillMaxWidth()
            ) {
                AnimatedContent(
                    targetState = step,
                    transitionSpec = {
                        fadeIn(animationSpec = tween(300)) with fadeOut(animationSpec = tween(300))
                    }
                ) { currentStep ->
                    Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
                        when (currentStep) {
                            ForgotPasswordStep.SEND_CODE -> {
                                ShoppitTextField(
                                    value = email,
                                    onValueChange = { email = it },
                                    label = "Email Address",
                                    placeholder = "Enter your email",
                                    leadingIcon = { Icon(Icons.Default.Email, contentDescription = null, tint = ShoppitPrimary) }
                                )
                            }
                            ForgotPasswordStep.VERIFY_CODE -> {
                                Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                                    ShoppitTextField(
                                        value = code,
                                        onValueChange = { code = it },
                                        label = "Reset Code",
                                        placeholder = "Enter 6-digit code",
                                        leadingIcon = { Icon(Icons.Default.VpnKey, contentDescription = null, tint = ShoppitPrimary) }
                                    )
                                    TextButton(
                                        onClick = { /* Resend Logic */ },
                                        modifier = Modifier.align(Alignment.End)
                                    ) {
                                        Text("Resend Code", color = ShoppitPrimary, fontWeight = FontWeight.Bold)
                                    }
                                }
                            }
                            ForgotPasswordStep.RESET_PASSWORD -> {
                                Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
                                    ShoppitTextField(
                                        value = newPassword,
                                        onValueChange = { newPassword = it },
                                        label = "New Password",
                                        placeholder = "Min 8 characters",
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
                                    ShoppitTextField(
                                        value = confirmPassword,
                                        onValueChange = { confirmPassword = it },
                                        label = "Confirm Password",
                                        placeholder = "Re-enter password",
                                        visualTransformation = PasswordVisualTransformation(),
                                        leadingIcon = { Icon(Icons.Default.CheckCircle, contentDescription = null, tint = ShoppitPrimary) }
                                    )
                                }
                            }
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(40.dp))

            ShoppitButton(
                text = when(step) {
                    ForgotPasswordStep.SEND_CODE -> "Send Code"
                    ForgotPasswordStep.VERIFY_CODE -> "Verify"
                    ForgotPasswordStep.RESET_PASSWORD -> "Reset Password"
                },
                isLoading = isLoading,
                onClick = {
                    when(step) {
                        ForgotPasswordStep.SEND_CODE -> {
                            if (email.isNotBlank()) {
                                step = ForgotPasswordStep.VERIFY_CODE
                            }
                        }
                        ForgotPasswordStep.VERIFY_CODE -> {
                            if (code.length >= 4) {
                                step = ForgotPasswordStep.RESET_PASSWORD
                            }
                        }
                        ForgotPasswordStep.RESET_PASSWORD -> {
                            if (newPassword.length >= 8 && newPassword == confirmPassword) {
                                onSuccess()
                            }
                        }
                    }
                },
                modifier = Modifier.fillMaxWidth()
            )
        }
    }
}
