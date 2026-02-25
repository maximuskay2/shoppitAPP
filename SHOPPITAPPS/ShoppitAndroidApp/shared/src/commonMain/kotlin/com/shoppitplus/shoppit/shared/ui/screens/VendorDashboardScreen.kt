package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
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
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.*
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatDecimal

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun VendorDashboardScreen(
    vendorName: String,
    walletBalance: Double,
    stats: StatsData?,
    analytics: VendorAnalyticsData?,
    onNotificationsClick: () -> Unit,
    onOrdersClick: () -> Unit,
    onProductsClick: () -> Unit,
    onSettingsClick: () -> Unit,
    onWithdrawClick: () -> Unit
) {
    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Column {
                        KineticHeadline(
                            text = "Dashboard",
                            scrollOffset = scrollState.value,
                            threshold = 200
                        )
                        Text(
                            "Welcome back, $vendorName",
                            style = MaterialTheme.typography.bodySmall,
                            color = Color.Gray
                        )
                    }
                },
                actions = {
                    IconButton(onClick = onNotificationsClick) {
                        BadgedBox(badge = { Badge { Text("3") } }) {
                            Icon(Icons.Default.Notifications, contentDescription = "Notifications")
                        }
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
            // High-end Background Depth
            Box(
                modifier = Modifier
                    .size(350.dp)
                    .offset(x = 200.dp, y = 100.dp)
                    .blur(120.dp)
                    .clip(CircleShape)
                    .background(Color(0xFF1565C0).copy(alpha = 0.05f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(24.dp)
            ) {
                // --- PREMIUM BENTO BALANCE CARD ---
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .shadow(12.dp, RoundedCornerShape(32.dp)),
                    shape = RoundedCornerShape(32.dp),
                    color = Color.Transparent
                ) {
                    Box(
                        modifier = Modifier
                            .background(
                                brush = Brush.linearGradient(
                                    listOf(Color(0xFF1565C0), Color(0xFF0D47A1))
                                )
                            )
                            .padding(28.dp)
                    ) {
                        Column {
                            Text("Total Wallet Balance", color = Color.White.copy(alpha = 0.7f), style = MaterialTheme.typography.labelMedium)
                            Text(
                                "₦${formatDecimal(walletBalance, 2)}",
                                color = Color.White,
                                style = MaterialTheme.typography.displaySmall,
                                fontWeight = FontWeight.Black,
                                letterSpacing = 1.sp
                            )

                            Spacer(modifier = Modifier.height(24.dp))

                            ShoppitButton(
                                text = "Instant Withdrawal",
                                onClick = onWithdrawClick,
                                containerColor = Color.White.copy(alpha = 0.2f),
                                contentColor = Color.White,
                                modifier = Modifier.fillMaxWidth().height(52.dp)
                            )
                        }
                    }
                }

                // --- STAGGERED BENTO QUICK ACTIONS ---
                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    // Large action (Products)
                    ActionBentoCard(
                        icon = Icons.Default.Inventory,
                        title = "Products",
                        subtitle = "Manage items",
                        modifier = Modifier.weight(1.2f).height(160.dp),
                        onClick = onProductsClick
                    )

                    // Stacked small actions (Orders & Settings)
                    Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        ActionBentoCard(
                            icon = Icons.Default.ReceiptLong,
                            title = "Orders",
                            subtitle = "Fulfill now",
                            modifier = Modifier.fillMaxWidth().height(72.dp),
                            onClick = onOrdersClick,
                            compact = true
                        )
                        ActionBentoCard(
                            icon = Icons.Default.Settings,
                            title = "Store",
                            subtitle = "Hours & Info",
                            modifier = Modifier.fillMaxWidth().height(72.dp),
                            onClick = onSettingsClick,
                            compact = true
                        )
                    }
                }

                // --- BENTO PERFORMANCE GRID ---
                if (stats != null) {
                    Text("Monthly Insight", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Black, modifier = Modifier.padding(start = 8.dp))

                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        StatBentoCard(
                            label = "Revenue",
                            value = "₦${stats.revenue.totalRevenue}",
                            color = Color(0xFFE91E63),
                            modifier = Modifier.weight(1.5f)
                        )
                        StatBentoCard(
                            label = "Completed",
                            value = stats.orders.completed.toString(),
                            color = ShoppitPrimary,
                            modifier = Modifier.weight(1f)
                        )
                    }
                }

                Spacer(modifier = Modifier.height(100.dp))
            }
        }
    }
}

@Composable
fun ActionBentoCard(
    icon: ImageVector,
    title: String,
    subtitle: String,
    modifier: Modifier,
    onClick: () -> Unit,
    compact: Boolean = false
) {
    BentoCard(
        modifier = modifier.clickable(onClick = onClick),
        elevation = 6.dp
    ) {
        if (compact) {
            Row(verticalAlignment = Alignment.CenterVertically, modifier = Modifier.fillMaxSize()) {
                Icon(icon, contentDescription = null, tint = ShoppitPrimary, modifier = Modifier.size(24.dp))
                Spacer(modifier = Modifier.width(12.dp))
                Column {
                    Text(title, style = MaterialTheme.typography.labelLarge, fontWeight = FontWeight.Bold)
                    Text(subtitle, style = MaterialTheme.typography.labelSmall, color = Color.Gray)
                }
            }
        } else {
            Column(verticalArrangement = Arrangement.Center, modifier = Modifier.fillMaxSize()) {
                Icon(icon, contentDescription = null, tint = ShoppitPrimary, modifier = Modifier.size(32.dp))
                Spacer(modifier = Modifier.height(12.dp))
                Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Black)
                Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            }
        }
    }
}

@Composable
fun StatBentoCard(label: String, value: String, color: Color, modifier: Modifier) {
    BentoCard(modifier = modifier, elevation = 4.dp) {
        Column {
            Box(modifier = Modifier.size(12.dp, 4.dp).clip(CircleShape).background(color))
            Spacer(modifier = Modifier.height(12.dp))
            Text(value, style = MaterialTheme.typography.headlineSmall, fontWeight = FontWeight.Black)
            Text(label, style = MaterialTheme.typography.labelSmall, color = Color.Gray)
        }
    }
}
