package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.draw.scale
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import kotlinx.coroutines.delay

@Composable
fun SplashScreen(
    onNavigate: () -> Unit
) {
    val infiniteTransition = rememberInfiniteTransition()

    val pulseScale by infiniteTransition.animateFloat(
        initialValue = 1f,
        targetValue = 1.3f, // Increased pulse for deeper 3D feel
        animationSpec = infiniteRepeatable(
            animation = tween(4000, easing = FastOutSlowInEasing),
            repeatMode = RepeatMode.Reverse
        )
    )

    val logoScale = remember { Animatable(0f) }
    val logoAlpha = remember { Animatable(0f) }

    LaunchedEffect(Unit) {
        logoScale.animateTo(
            targetValue = 1f,
            animationSpec = spring(
                dampingRatio = Spring.DampingRatioMediumBouncy,
                stiffness = Spring.StiffnessLow
            )
        )
        logoAlpha.animateTo(1f, tween(1000))
        delay(2500)
        onNavigate()
    }

    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.radialGradient( // Switched to Radial for better 3D depth
                    colors = listOf(
                        ShoppitPrimary.copy(alpha = 0.7f),
                        ShoppitPrimary
                    ),
                    radius = 1000f
                )
            ),
        contentAlignment = Alignment.Center
    ) {
        // Enhanced 3D Glass Depth Circles
        Box(
            modifier = Modifier
                .size(400.dp)
                .offset(x = (-150).dp, y = (-200).dp)
                .scale(pulseScale)
                .blur(80.dp)
                .clip(CircleShape)
                .background(GlassWhite.copy(alpha = 0.15f))
        )

        Box(
            modifier = Modifier
                .size(300.dp)
                .offset(x = 150.dp, y = 250.dp)
                .scale(pulseScale * 0.9f)
                .blur(60.dp)
                .clip(CircleShape)
                .background(Color.White.copy(alpha = 0.08f))
        )

        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier
                .scale(logoScale.value)
                .alpha(logoAlpha.value)
        ) {
            Box(
                modifier = Modifier
                    .size(140.dp)
                    .clip(CircleShape)
                    .background(GlassWhite.copy(alpha = 0.25f))
                    .padding(4.dp)
                    .shadow(elevation = 20.dp, shape = CircleShape, spotColor = Color.Black.copy(alpha = 0.3f)),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    text = "S",
                    color = Color.White,
                    fontSize = 72.sp,
                    fontWeight = FontWeight.Black
                )
            }

            Spacer(modifier = Modifier.height(32.dp))

            Text(
                text = "Shoppit",
                color = Color.White,
                style = MaterialTheme.typography.displayMedium,
                fontWeight = FontWeight.Black,
                letterSpacing = 6.sp
            )

            Text(
                text = "PREMIUM DELIVERY EXPERIENCE",
                color = Color.White.copy(alpha = 0.6f),
                style = MaterialTheme.typography.labelLarge,
                letterSpacing = 3.sp
            )
        }
    }
}
