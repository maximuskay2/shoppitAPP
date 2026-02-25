package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VendorOrderDetailScreen(
    order: OrderDetail,
    onBack: () -> Unit,
    onUpdateStatus: (String) -> Unit,
    onTrackOrder: () -> Unit,
    onShareReceipt: () -> Unit
) {
    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "Order Details",
                        scrollOffset = scrollState.value,
                        threshold = 200
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                actions = {
                    IconButton(onClick = onShareReceipt) {
                        Icon(Icons.Default.Share, contentDescription = "Share Receipt")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Transparent)
            )
        },
        bottomBar = {
            // High-end Floating Bento Action Bar
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
                    .shadow(12.dp, RoundedCornerShape(24.dp)),
                shape = RoundedCornerShape(24.dp),
                color = Color.White.copy(alpha = 0.95f)
            ) {
                Row(
                    modifier = Modifier.padding(16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    ShoppitButton(
                        text = "Update Status",
                        onClick = { /* Show status picker */ },
                        modifier = Modifier.weight(1f)
                    )
                    ShoppitButton(
                        text = "Track",
                        onClick = onTrackOrder,
                        containerColor = Color.White,
                        contentColor = ShoppitPrimary,
                        modifier = Modifier.weight(0.6f)
                    )
                }
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(padding)
        ) {
            // Background Depth
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = 200.dp, y = (-50).dp)
                    .blur(120.dp)
                    .clip(CircleShape)
                    .background(Color(0xFF1565C0).copy(alpha = 0.05f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(20.dp)
            ) {
                // --- BENTO STATUS SECTION ---
                BentoCard {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Column {
                            Text("Current State", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(
                                text = order.status.uppercase(),
                                style = MaterialTheme.typography.titleLarge,
                                fontWeight = FontWeight.Black,
                                color = ShoppitPrimary
                            )
                        }
                        Column(horizontalAlignment = Alignment.End) {
                            Text("Tracking ID", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                            Text(order.trackingId, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyMedium)
                        }
                    }
                }

                // --- BENTO ITEMS SECTION ---
                Text("Order Items", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Black, color = ShoppitTextPrimary.copy(alpha = 0.6f), modifier = Modifier.padding(start = 8.dp))
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        order.lineItems.forEach { item ->
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Surface(
                                    modifier = Modifier.size(50.dp),
                                    shape = RoundedCornerShape(12.dp),
                                    color = Color.LightGray.copy(alpha = 0.1f)
                                ) {
                                    Box(contentAlignment = Alignment.Center) {
                                        Icon(Icons.Default.Inventory, contentDescription = null, tint = Color.Gray.copy(alpha = 0.5f))
                                    }
                                }
                                Spacer(modifier = Modifier.width(16.dp))
                                Column(modifier = Modifier.weight(1f)) {
                                    Text(item.productName ?: item.product?.name ?: "Product", fontWeight = FontWeight.Bold)
                                    Text("${item.quantity} x ₦${formatCurrency(item.price)}", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                                }
                            }
                        }
                    }
                }

                // --- BENTO CUSTOMER INFO ---
                Text("Customer & Logistics", style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Black, color = ShoppitTextPrimary.copy(alpha = 0.6f), modifier = Modifier.padding(start = 8.dp))
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        DetailInfoRow(icon = Icons.Default.Person, label = "Customer", value = order.user.name)
                        DetailInfoRow(icon = Icons.Default.Phone, label = "Contact", value = order.user.phone ?: "Not provided")
                        DetailInfoRow(icon = Icons.Default.LocationOn, label = "Address", value = order.user.address ?: "Not provided")
                    }
                }

                // --- BENTO PAYMENT SUMMARY ---
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                        val subtotal = order.grossTotalAmount - order.deliveryFee
                        SummaryDetailRow("Subtotal", "₦${formatCurrency(subtotal)}")
                        SummaryDetailRow("Delivery", "₦${formatCurrency(order.deliveryFee)}")
                        HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp), color = Color.LightGray.copy(alpha = 0.2f))
                        SummaryDetailRow("Grand Total", "₦${formatCurrency(order.grossTotalAmount)}", isTotal = true)
                    }
                }

                Spacer(modifier = Modifier.height(140.dp))
            }
        }
    }
}

@Composable
fun DetailInfoRow(icon: androidx.compose.ui.graphics.vector.ImageVector, label: String, value: String) {
    Row(verticalAlignment = Alignment.CenterVertically) {
        Surface(modifier = Modifier.size(32.dp), shape = CircleShape, color = ShoppitPrimary.copy(alpha = 0.1f)) {
            Icon(icon, contentDescription = null, tint = ShoppitPrimary, modifier = Modifier.padding(8.dp))
        }
        Spacer(modifier = Modifier.width(16.dp))
        Column {
            Text(label, style = MaterialTheme.typography.labelSmall, color = Color.Gray)
            Text(value, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun SummaryDetailRow(label: String, value: String, isTotal: Boolean = false) {
    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
        Text(
            label,
            style = if (isTotal) MaterialTheme.typography.titleMedium else MaterialTheme.typography.bodyMedium,
            fontWeight = if (isTotal) FontWeight.Bold else FontWeight.Normal
        )
        Text(
            value,
            style = if (isTotal) MaterialTheme.typography.titleMedium else MaterialTheme.typography.bodyMedium,
            fontWeight = if (isTotal) FontWeight.ExtraBold else FontWeight.Bold,
            color = if (isTotal) ShoppitPrimary else ShoppitTextPrimary
        )
    }
}
