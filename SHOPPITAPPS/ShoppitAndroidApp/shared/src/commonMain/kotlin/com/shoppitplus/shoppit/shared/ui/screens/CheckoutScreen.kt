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
import com.shoppitplus.shoppit.shared.models.CartVendor
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatCurrency

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun CheckoutScreen(
    vendorCart: CartVendor,
    onBack: () -> Unit,
    onOrderNotesClick: () -> Unit,
    onGiftOptionsClick: () -> Unit,
    onProcessPayment: (useWallet: Boolean) -> Unit
) {
    var useWallet by remember { mutableStateOf(false) }
    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "Checkout",
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
        },
        bottomBar = {
            Surface(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(16.dp)
                    .clip(RoundedCornerShape(24.dp)),
                color = Color.White.copy(alpha = 0.95f),
                shadowElevation = 8.dp
            ) {
                Column(modifier = Modifier.padding(16.dp)) {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.SpaceBetween,
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("Grand Total", style = MaterialTheme.typography.bodyMedium, color = Color.Gray)
                        Text(
                            "₦${formatCurrency(vendorCart.vendorTotal)}",
                            style = MaterialTheme.typography.headlineSmall,
                            fontWeight = FontWeight.Black,
                            color = ShoppitPrimary
                        )
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                    ShoppitButton(
                        text = "Pay Now",
                        onClick = { onProcessPayment(useWallet) },
                        modifier = Modifier.fillMaxWidth()
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
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = (-150).dp, y = 100.dp)
                    .blur(120.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.08f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(20.dp)
            ) {
                // Bento Header
                BentoCard {
                    Row(verticalAlignment = Alignment.CenterVertically) {
                        Box(
                            modifier = Modifier.size(50.dp).clip(CircleShape).background(ShoppitPrimary.copy(alpha = 0.1f)),
                            contentAlignment = Alignment.Center
                        ) {
                            Text(vendorCart.vendor.name.take(1), fontWeight = FontWeight.Bold, color = ShoppitPrimary)
                        }
                        Spacer(modifier = Modifier.width(16.dp))
                        Column {
                            Text(vendorCart.vendor.name, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
                            Text("${vendorCart.itemCount} items in this order", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                        }
                    }
                }

                // Bento Delivery Options
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        ActionRow(
                            icon = Icons.Default.Edit,
                            title = "Add Order Notes",
                            onClick = onOrderNotesClick
                        )
                        ActionRow(
                            icon = Icons.Default.CardGiftcard,
                            title = "Send as Gift",
                            onClick = onGiftOptionsClick
                        )
                    }
                }

                // Bento Payment Method
                Text(
                    "Payment Method",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Bold,
                    color = ShoppitTextPrimary.copy(alpha = 0.6f),
                    modifier = Modifier.padding(start = 8.dp)
                )
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(12.dp)) {
                        PaymentOptionRow(
                            icon = Icons.Default.AccountBalanceWallet,
                            title = "Wallet",
                            subtitle = "Pay from your Shoppit balance",
                            isSelected = useWallet,
                            onClick = { useWallet = true }
                        )
                        PaymentOptionRow(
                            icon = Icons.Default.CreditCard,
                            title = "Online Payment",
                            subtitle = "Pay via Card or Bank Transfer",
                            isSelected = !useWallet,
                            onClick = { useWallet = false }
                        )
                    }
                }

                // Bento Summary
                BentoCard {
                    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
                        CheckoutSummaryInfoRow("Subtotal", "₦${formatCurrency(vendorCart.subtotal)}")
                        CheckoutSummaryInfoRow("Delivery Fee", "₦${formatCurrency(vendorCart.deliveryFee)}")
                        HorizontalDivider(modifier = Modifier.padding(vertical = 4.dp), color = Color.LightGray.copy(alpha = 0.2f))
                        CheckoutSummaryInfoRow("Total", "₦${formatCurrency(vendorCart.vendorTotal)}", isTotal = true)
                    }
                }

                Spacer(modifier = Modifier.height(120.dp))
            }
        }
    }
}

@Composable
fun ActionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    title: String,
    onClick: () -> Unit
) {
    Row(
        modifier = Modifier.fillMaxWidth().clickable(onClick = onClick).padding(vertical = 4.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Icon(icon, contentDescription = null, tint = ShoppitPrimary, modifier = Modifier.size(24.dp))
        Spacer(modifier = Modifier.width(16.dp))
        Text(title, style = MaterialTheme.typography.bodyMedium, fontWeight = FontWeight.Medium, modifier = Modifier.weight(1f))
        Icon(Icons.Default.ChevronRight, contentDescription = null, tint = Color.LightGray)
    }
}

@Composable
fun PaymentOptionRow(
    icon: androidx.compose.ui.graphics.vector.ImageVector,
    title: String,
    subtitle: String,
    isSelected: Boolean,
    onClick: () -> Unit
) {
    val borderColor = if (isSelected) ShoppitPrimary else Color.Transparent
    val backgroundColor = if (isSelected) ShoppitPrimary.copy(alpha = 0.05f) else Color.Transparent

    Row(
        modifier = Modifier
            .fillMaxWidth()
            .clip(RoundedCornerShape(16.dp))
            .background(backgroundColor)
            .border(1.dp, borderColor, RoundedCornerShape(16.dp))
            .clickable(onClick = onClick)
            .padding(16.dp),
        verticalAlignment = Alignment.CenterVertically
    ) {
        Box(
            modifier = Modifier.size(40.dp).clip(CircleShape).background(if (isSelected) ShoppitPrimary else Color.LightGray.copy(alpha = 0.2f)),
            contentAlignment = Alignment.Center
        ) {
            Icon(icon, contentDescription = null, tint = if (isSelected) Color.White else Color.Gray)
        }
        Spacer(modifier = Modifier.width(16.dp))
        Column(modifier = Modifier.weight(1f)) {
            Text(title, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.bodyMedium)
            Text(subtitle, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
        }
        RadioButton(
            selected = isSelected,
            onClick = onClick,
            colors = RadioButtonDefaults.colors(selectedColor = ShoppitPrimary)
        )
    }
}

@Composable
fun CheckoutSummaryInfoRow(label: String, value: String, isTotal: Boolean = false) {
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
