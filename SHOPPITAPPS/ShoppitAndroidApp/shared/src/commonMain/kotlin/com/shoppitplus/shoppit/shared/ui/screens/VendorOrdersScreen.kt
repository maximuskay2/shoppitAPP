package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
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
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VendorOrdersScreen(
    orders: List<OrderDetail>,
    onBack: () -> Unit,
    onOrderClick: (OrderDetail) -> Unit
) {
    var selectedFilter by remember { mutableStateOf("All") }
    val filters = listOf("All", "New", "Pending", "Completed", "Cancelled")
    val scrollState = rememberScrollState()

    val filteredOrders = remember(selectedFilter, orders) {
        when (selectedFilter) {
            "All" -> orders
            "New" -> orders.filter { it.status.uppercase() in listOf("PENDING", "PAID") }
            "Pending" -> orders.filter { it.status.uppercase() == "PENDING" }
            "Completed" -> orders.filter { it.status.uppercase() == "COMPLETED" }
            "Cancelled" -> orders.filter { it.status.uppercase() == "CANCELLED" }
            else -> orders
        }
    }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "Store Orders",
                        scrollOffset = scrollState.value,
                        threshold = 200
                    )
                },
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
            // Background Depth
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = 200.dp, y = 100.dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(Color(0xFF1565C0).copy(alpha = 0.05f))
            )

            Column {
                // High-tactile Filter Row
                LazyRow(
                    modifier = Modifier.fillMaxWidth().padding(vertical = 12.dp),
                    contentPadding = PaddingValues(horizontal = 16.dp),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(filters) { filter ->
                        FilterChip(
                            selected = selectedFilter == filter,
                            onClick = { selectedFilter = filter },
                            label = { Text(filter, fontWeight = if (selectedFilter == filter) FontWeight.Bold else FontWeight.Medium) },
                            colors = FilterChipDefaults.filterChipColors(
                                selectedContainerColor = ShoppitPrimary,
                                selectedLabelColor = Color.White,
                                containerColor = GlassWhite.copy(alpha = 0.5f)
                            ),
                            shape = RoundedCornerShape(16.dp)
                        )
                    }
                }

                if (filteredOrders.isEmpty()) {
                    VendorEmptyOrdersState(selectedFilter)
                } else {
                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        items(filteredOrders) { order ->
                            VendorOrderBentoCard(order = order, onClick = { onOrderClick(order) })
                        }
                        item { Spacer(modifier = Modifier.height(100.dp)) }
                    }
                }
            }
        }
    }
}

@Composable
private fun VendorEmptyOrdersState(filter: String) {
    Column(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(Icons.Default.Inbox, contentDescription = null, modifier = Modifier.size(80.dp).alpha(0.1f))
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            "No $filter orders",
            style = MaterialTheme.typography.titleMedium,
            color = Color.Gray
        )
    }
}

@Composable
fun VendorOrderBentoCard(order: OrderDetail, onClick: () -> Unit) {
    BentoCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        elevation = 6.dp
    ) {
        Column {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        text = order.lineItems.firstOrNull()?.productName ?: order.lineItems.firstOrNull()?.product?.name ?: "Multiple Items",
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Black,
                        maxLines = 1,
                        color = ShoppitTextPrimary
                    )
                    Text(
                        text = "Track ID: ${order.trackingId}",
                        style = MaterialTheme.typography.labelSmall,
                        color = Color.Gray,
                        letterSpacing = 1.sp
                    )
                }

                // Status Bento Badge
                val (statusColor, statusBg) = when (order.status.uppercase()) {
                    "PAID", "DELIVERED", "COMPLETED" -> ShoppitPrimary to ShoppitPrimary.copy(alpha = 0.1f)
                    "PENDING" -> Color(0xFFFF9800) to Color(0xFFFF9800).copy(alpha = 0.1f)
                    "CANCELLED" -> Color.Red to Color.Red.copy(alpha = 0.1f)
                    else -> Color.Gray to Color.Gray.copy(alpha = 0.1f)
                }

                Surface(
                    shape = RoundedCornerShape(12.dp),
                    color = statusBg
                ) {
                    Text(
                        text = order.status.uppercase(),
                        modifier = Modifier.padding(horizontal = 10.dp, vertical = 6.dp),
                        style = MaterialTheme.typography.labelSmall,
                        fontWeight = FontWeight.Black,
                        color = statusColor
                    )
                }
            }

            Spacer(modifier = Modifier.height(20.dp))
            HorizontalDivider(color = Color.LightGray.copy(alpha = 0.2f))
            Spacer(modifier = Modifier.height(20.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Box(
                        modifier = Modifier.size(32.dp).clip(CircleShape).background(Color.LightGray.copy(alpha = 0.2f)),
                        contentAlignment = Alignment.Center
                    ) {
                        Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.size(16.dp), tint = Color.Gray)
                    }
                    Spacer(modifier = Modifier.width(12.dp))
                    Text(
                        text = order.receiverName ?: order.user.name,
                        style = MaterialTheme.typography.bodyMedium,
                        fontWeight = FontWeight.Bold
                    )
                }

                Column(horizontalAlignment = Alignment.End) {
                    Text("Earnings", style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                    Text(
                        "₦${formatCurrency(order.grossTotalAmount)}",
                        fontWeight = FontWeight.Black,
                        color = ShoppitPrimary,
                        fontSize = 20.sp
                    )
                }
            }
        }
    }
}
