package com.shoppitplus.shoppit.shared.ui.components

import androidx.compose.animation.core.*
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@Composable
fun KineticHeadline(
    text: String,
    scrollOffset: Int,
    modifier: Modifier = Modifier,
    color: Color = ShoppitTextPrimary,
    maxWeight: Int = 900,
    minWeight: Int = 400,
    threshold: Int = 300
) {
    // Dynamically calculate weight based on how far the user has scrolled
    // Weight decreases as the headline moves up the screen
    val targetWeight = remember(scrollOffset) {
        val progress = (scrollOffset.toFloat() / threshold).coerceIn(0f, 1f)
        (maxWeight - ((maxWeight - minWeight) * progress)).toInt()
    }

    val animatedWeight by animateIntAsState(
        targetValue = targetWeight,
        animationSpec = spring(
            dampingRatio = Spring.DampingRatioLowBouncy,
            stiffness = Spring.StiffnessVeryLow
        )
    )

    Text(
        text = text,
        style = MaterialTheme.typography.headlineLarge,
        fontWeight = FontWeight(animatedWeight),
        color = color,
        modifier = modifier
    )
}
