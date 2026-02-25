package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Remove
import androidx.compose.material.icons.filled.Share
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.ProductDto
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProductDetailScreen(
    product: ProductDto,
    onBack: () -> Unit,
    onAddToCart: (Int) -> Unit
) {
    var quantity by remember { mutableStateOf(1) }
    val scrollState = rememberScrollState()

    Scaffold(
        bottomBar = {
            // High-end 3D Glass Floating Bottom Bar
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 24.dp)
            ) {
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(88.dp)
                        .shadow(20.dp, RoundedCornerShape(32.dp), spotColor = ShoppitPrimary.copy(alpha = 0.3f)),
                    shape = RoundedCornerShape(32.dp),
                    color = Color.White.copy(alpha = 0.95f),
                    border = border(width = 1.dp, color = Color.White.copy(alpha = 0.5f), shape = RoundedCornerShape(32.dp))
                ) {
                    Row(
                        modifier = Modifier.padding(horizontal = 24.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        val total = (product.discountPrice ?: product.price) * quantity

                        Column(modifier = Modifier.weight(1f)) {
                            Text("Grand Total", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(
                                "₦${formatCurrency(total)}",
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Black,
                                color = ShoppitPrimary
                            )
                        }

                        ShoppitButton(
                            text = "Add to Cart",
                            onClick = { onAddToCart(quantity) },
                            modifier = Modifier.width(160.dp)
                        )
                    }
                }
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
        ) {
            // Immersive Radial Background Depth
            Box(
                modifier = Modifier
                    .size(400.dp)
                    .offset(x = 200.dp, y = (-100).dp)
                    .blur(120.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.08f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
            ) {
                // Immersive Hero Section
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(420.dp)
                ) {
                    // Image Placeholder with depth vignette
                    Box(
                        modifier = Modifier
                            .fillMaxSize()
                            .background(
                                Brush.verticalGradient(
                                    listOf(Color.Black.copy(alpha = 0.1f), Color.Transparent, Color.Black.copy(alpha = 0.05f))
                                )
                            ),
                        contentAlignment = Alignment.Center
                    ) {
                        Surface(
                            modifier = Modifier
                                .size(280.dp)
                                .shadow(30.dp, CircleShape, spotColor = Color.Black.copy(alpha = 0.2f)),
                            shape = CircleShape,
                            color = Color.White.copy(alpha = 0.4f)
                        ) {
                            Text("IMAGE", modifier = Modifier.align(Alignment.Center), color = Color.Gray)
                        }
                    }

                    // Floating Glass Buttons
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(24.dp),
                        horizontalArrangement = Arrangement.SpaceBetween
                    ) {
                        GlassFloatingButton(icon = Icons.Default.ArrowBack, onClick = onBack)
                        GlassFloatingButton(icon = Icons.Default.Share, onClick = {})
                    }
                }

                // Bento-Style Content Body
                Column(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(horizontal = 24.dp)
                ) {
                    KineticHeadline(
                        text = product.name,
                        scrollOffset = scrollState.value,
                        threshold = 300
                    )

                    Spacer(modifier = Modifier.height(12.dp))

                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Text(
                            text = "₦${formatCurrency(product.discountPrice ?: product.price)}",
                            style = MaterialTheme.typography.displaySmall,
                            fontWeight = FontWeight.Black,
                            color = ShoppitPrimary
                        )
                        if (product.discountPrice != null) {
                            Spacer(modifier = Modifier.width(16.dp))
                            Text(
                                text = "₦${formatCurrency(product.price)}",
                                style = MaterialTheme.typography.titleMedium,
                                textDecoration = androidx.compose.ui.text.style.TextDecoration.LineThrough,
                                color = Color.Gray.copy(alpha = 0.5f)
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(32.dp))

                    // Bento Card for Description
                    BentoCard(modifier = Modifier.fillMaxWidth()) {
                        Column {
                            Text(
                                text = "About this item",
                                style = MaterialTheme.typography.titleMedium,
                                fontWeight = FontWeight.Bold
                            )
                            Spacer(modifier = Modifier.height(12.dp))
                            Text(
                                text = product.description ?: "No description available.",
                                style = MaterialTheme.typography.bodyLarge,
                                color = ShoppitTextPrimary.copy(alpha = 0.7f),
                                lineHeight = 24.sp
                            )
                        }
                    }

                    Spacer(modifier = Modifier.height(24.dp))

                    // Tactile Quantity Bento
                    BentoCard(
                        modifier = Modifier.width(200.dp),
                        cornerRadius = 20.dp,
                        backgroundColor = Color.White
                    ) {
                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            IconButton(onClick = { if (quantity > 1) quantity-- }) {
                                Icon(Icons.Default.Remove, contentDescription = null, tint = ShoppitPrimary)
                            }
                            Text(
                                text = quantity.toString(),
                                style = MaterialTheme.typography.headlineSmall,
                                fontWeight = FontWeight.Black
                            )
                            IconButton(onClick = { quantity++ }) {
                                Icon(Icons.Default.Add, contentDescription = null, tint = ShoppitPrimary)
                            }
                        }
                    }

                    Spacer(modifier = Modifier.height(160.dp))
                }
            }
        }
    }
}

@Composable
fun GlassFloatingButton(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    onClick: () -> Unit
) {
    Surface(
        modifier = Modifier
            .size(48.dp)
            .shadow(10.dp, CircleShape)
            .clickable(onClick = onClick),
        shape = CircleShape,
        color = Color.White.copy(alpha = 0.6f),
        border = border(width = 1.dp, color = Color.White.copy(alpha = 0.3f), shape = CircleShape)
    ) {
        Icon(icon, contentDescription = null, modifier = Modifier.padding(12.dp))
    }
}

@Composable
private fun border(width: androidx.compose.ui.unit.Dp, color: Color, shape: androidx.compose.ui.graphics.Shape) =
    androidx.compose.foundation.BorderStroke(width, color)
