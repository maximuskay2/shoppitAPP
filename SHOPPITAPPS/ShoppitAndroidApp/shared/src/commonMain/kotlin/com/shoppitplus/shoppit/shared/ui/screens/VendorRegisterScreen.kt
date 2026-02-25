package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Business
import androidx.compose.material.icons.filled.Email
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.components.ShoppitTextField
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@Composable
fun VendorRegisterScreen(
    onRegisterSuccess: (email: String) -> Unit,
    onNavigateToLogin: () -> Unit
) {
    var email by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }

    val scrollState = rememberScrollState()

    // Kinetic Typography Animation
    val headlineWeight by animateIntAsState(
        targetValue = if (scrollState.value > 100) 400 else 900,
        animationSpec = tween(500)
    )

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(MaterialTheme.colorScheme.background)
    ) {
        // --- 3D DEPTH BACKGROUND (Vendor specific blue/green mix) ---
        Box(
            modifier = Modifier
                .size(350.dp)
                .offset(x = 150.dp, y = 200.dp)
                .blur(100.dp)
                .clip(CircleShape)
                .background(Color(0xFF004D40).copy(alpha = 0.1f))
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
            Text(
                text = "Partner with Us",
                style = MaterialTheme.typography.headlineLarge,
                fontWeight = FontWeight(headlineWeight),
                color = ShoppitTextPrimary
            )

            Text(
                text = "Grow your business with Shoppit Vendor",
                style = MaterialTheme.typography.bodyMedium,
                color = ShoppitTextPrimary.copy(alpha = 0.6f),
                modifier = Modifier.padding(top = 8.dp)
            )

            Spacer(modifier = Modifier.height(48.dp))

            // Glassmorphic Input Card
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .clip(MaterialTheme.shapes.extraLarge)
                    .background(GlassWhite.copy(alpha = 0.5f))
                    .padding(16.dp)
            ) {
                ShoppitTextField(
                    value = email,
                    onValueChange = { email = it },
                    label = "Business Email",
                    placeholder = "e.g. sales@yourstore.com",
                    leadingIcon = { Icon(Icons.Default.Business, contentDescription = null, tint = ShoppitPrimary) }
                )
            }

            Spacer(modifier = Modifier.height(40.dp))

            ShoppitButton(
                text = "Create Store Account",
                isLoading = isLoading,
                onClick = {
                    if (email.isNotBlank()) {
                        isLoading = true
                        // Logic integration via shared ApiClient later
                    }
                },
                modifier = Modifier.fillMaxWidth()
            )

            Spacer(modifier = Modifier.height(24.dp))

            // Footer
            Row(verticalAlignment = Alignment.CenterVertically) {
                Text(
                    "Already a partner?",
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
