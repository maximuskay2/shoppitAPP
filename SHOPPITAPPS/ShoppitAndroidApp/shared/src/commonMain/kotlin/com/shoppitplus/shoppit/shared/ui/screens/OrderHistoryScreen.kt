package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun OrderHistoryScreen(
    orders: List<OrderDetail>,
    onBack: () -> Unit,
    onOrderClick: (String) -> Unit
) {
    val scrollState = rememberScrollState()

    // Kinetic typography animation
    val titleWeight by animateIntAsState(
        targetValue = if (scrollState.value.toFloat() > 100f) 400 else 900,
        animationSpec = tween(500)
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Order History", fontWeight = FontWeight(titleWeight)) },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Transparent)
            )
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(padding)
        ) {
            // Background Depth blurs
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = (-100).dp, y = 200.dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.05f))
            )

            if (orders.isEmpty()) {
                EmptyOrdersState()
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(16.dp)
                ) {
                    items(orders) { order ->
                        ConsumerOrderCard(order = order, onClick = { onOrderClick(order.id) })
                    }
                    item { Spacer(modifier = Modifier.height(80.dp)) }
                }
            }
        }
    }
}

@Composable
fun ConsumerOrderCard(order: OrderDetail, onClick: () -> Unit) {
    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(4.dp, RoundedCornerShape(24.dp))
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(24.dp),
        color = GlassWhite.copy(alpha = 0.8f)
    ) {
        Column(modifier = Modifier.padding(16.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                val firstItem = order.lineItems.firstOrNull()
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = firstItem?.productName ?: firstItem?.product?.name ?: "Order Item",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        maxLines = 1
                    )
                    if (order.lineItems.size > 1) {
                        Text(
                            text = "+ ${order.lineItems.size - 1} more item${if (order.lineItems.size > 2) "s" else ""}",
                            style = MaterialTheme.typography.bodySmall,
                            color = Color.Gray
                        )
                    }
                }

                // Status Badge
                StatusBadge(status = order.status)
            }

            Spacer(modifier = Modifier.height(16.dp))
            HorizontalDivider(color = Color.LightGray.copy(alpha = 0.2f))
            Spacer(modifier = Modifier.height(16.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Bottom
            ) {
                Column {
                    Text("Total Amount", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                    Text(
                        "₦${formatCurrency(order.grossTotalAmount)}",
                        style = MaterialTheme.typography.titleLarge,
                        fontWeight = FontWeight.Black,
                        color = ShoppitPrimary
                    )
                }

                Text(
                    text = order.createdAt.take(10), // Simple date display
                    style = MaterialTheme.typography.bodySmall,
                    color = Color.Gray
                )
            }
        }
    }
}

@Composable
fun StatusBadge(status: String) {
    val (statusColor, statusBg) = when (status.uppercase()) {
        "PAID", "DELIVERED", "COMPLETED" -> ShoppitPrimary to ShoppitPrimary.copy(alpha = 0.1f)
        "PENDING" -> Color(0xFFFF9800) to Color(0xFFFF9800).copy(alpha = 0.1f)
        "CANCELLED" -> Color.Red to Color.Red.copy(alpha = 0.1f)
        else -> Color.Gray to Color.Gray.copy(alpha = 0.1f)
    }

    Surface(
        shape = RoundedCornerShape(8.dp),
        color = statusBg
    ) {
        Text(
            text = status,
            modifier = Modifier.padding(horizontal = 8.dp, vertical = 4.dp),
            style = MaterialTheme.typography.labelSmall,
            fontWeight = FontWeight.Bold,
            color = statusColor
        )
    }
}

@Composable
private fun EmptyOrdersState() {
    Column(
        modifier = Modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.Receipt,
            contentDescription = null,
            modifier = Modifier.size(100.dp).alpha(0.1f)
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            "No orders yet",
            style = MaterialTheme.typography.titleMedium,
            color = Color.Gray
        )
    }
}
