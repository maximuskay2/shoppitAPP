package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
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
import com.shoppitplus.shoppit.shared.models.NotificationItem
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun NotificationScreen(
    notifications: List<NotificationItem>,
    onBack: () -> Unit,
    onNotificationClick: (NotificationItem) -> Unit,
    onRefresh: () -> Unit
) {
    val scrollState = rememberScrollState()

    val titleWeight by animateIntAsState(
        targetValue = if (scrollState.value > 100) 400 else 900,
        animationSpec = tween(500)
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("Notifications", fontWeight = FontWeight(titleWeight)) },
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
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = 150.dp, y = 200.dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.05f))
            )

            if (notifications.isEmpty()) {
                EmptyNotificationsState()
            } else {
                LazyColumn(
                    modifier = Modifier.fillMaxSize(),
                    contentPadding = PaddingValues(16.dp),
                    verticalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(notifications) { notification ->
                        NotificationItemRow(
                            notification = notification,
                            onClick = { onNotificationClick(notification) }
                        )
                    }
                    item { Spacer(modifier = Modifier.height(80.dp)) }
                }
            }
        }
    }
}

@Composable
fun NotificationItemRow(
    notification: NotificationItem,
    onClick: () -> Unit
) {
    val isRead = notification.readAt != null

    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .shadow(if (isRead) 1.dp else 4.dp, RoundedCornerShape(20.dp))
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(20.dp),
        color = if (isRead) GlassWhite.copy(alpha = 0.5f) else Color.White
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            Box(
                modifier = Modifier
                    .size(48.dp)
                    .clip(CircleShape)
                    .background(
                        if (isRead) Color.LightGray.copy(alpha = 0.2f)
                        else ShoppitPrimary.copy(alpha = 0.1f)
                    ),
                contentAlignment = Alignment.Center
            ) {
                val icon = when {
                    notification.type.contains("order", ignoreCase = true) -> Icons.Default.Receipt
                    notification.type.contains("payout", ignoreCase = true) -> Icons.Default.Payments
                    else -> Icons.Default.Notifications
                }
                Icon(
                    imageVector = icon,
                    contentDescription = null,
                    tint = if (isRead) Color.Gray else ShoppitPrimary,
                    modifier = Modifier.size(24.dp)
                )
            }

            Spacer(modifier = Modifier.width(16.dp))

            Column(modifier = Modifier.weight(1f)) {
                val data = notification.data
                val title = when {
                    data.orderId != null -> "Order #${data.trackingId ?: "Update"}"
                    data.vendorAmount != null -> "Payout Received"
                    else -> "New Message"
                }

                Text(
                    text = title,
                    style = MaterialTheme.typography.bodyLarge,
                    fontWeight = if (isRead) FontWeight.Medium else FontWeight.Bold,
                    color = if (isRead) Color.Gray else ShoppitTextPrimary
                )

                val amount = data.amount
                val description = when {
                    data.customerName != null -> "Customer: ${data.customerName}"
                    amount != null -> "Total: ₦${formatCurrency(amount)}"
                    else -> "Tap to view details"
                }

                Text(
                    text = description,
                    style = MaterialTheme.typography.bodySmall,
                    color = Color.Gray
                )
            }

            if (!isRead) {
                Box(
                    modifier = Modifier
                        .size(8.dp)
                        .clip(CircleShape)
                        .background(ShoppitPrimary)
                )
            }
        }
    }
}

@Composable
fun EmptyNotificationsState() {
    Column(
        modifier = Modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.Notifications,
            contentDescription = null,
            modifier = Modifier.size(100.dp).alpha(0.1f)
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text("No notifications yet", style = MaterialTheme.typography.titleMedium, color = Color.Gray)
    }
}
