package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
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
import com.shoppitplus.shoppit.shared.models.WalletTransaction
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary
import com.shoppitplus.shoppit.shared.utils.formatDecimal

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun WalletScreen(
    balance: Double,
    transactions: List<WalletTransaction>,
    onBack: () -> Unit,
    onAddMoney: () -> Unit,
    onWithdraw: () -> Unit
) {
    val listState = rememberLazyListState()

    // Kinetic typography animation
    val titleWeight by animateIntAsState(
        targetValue = if (listState.firstVisibleItemIndex > 0) 400 else 900,
        animationSpec = tween(500)
    )

    Scaffold(
        topBar = {
            TopAppBar(
                title = { Text("My Wallet", fontWeight = if (titleWeight >= 700) FontWeight.Bold else FontWeight.Normal) },
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
                    .offset(x = 200.dp, y = (-50).dp)
                    .blur(120.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.08f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(24.dp)
            ) {
                // Balance Card (High-end 3D design)
                Surface(
                    modifier = Modifier
                        .fillMaxWidth()
                        .shadow(12.dp, RoundedCornerShape(32.dp))
                        .clip(RoundedCornerShape(32.dp))
                        .background(Brush.linearGradient(colors = listOf(ShoppitPrimary, Color(0xFF1B5E20)))),
                    shape = RoundedCornerShape(32.dp),
                    color = Color.Transparent
                ) {
                    Column(
                        modifier = Modifier.padding(32.dp),
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            "Available Balance",
                            color = Color.White.copy(alpha = 0.7f),
                            style = MaterialTheme.typography.labelMedium
                        )
                        Text(
                            "₦${formatDecimal(balance, 2)}",
                            color = Color.White,
                            style = MaterialTheme.typography.displaySmall,
                            fontWeight = FontWeight.Black,
                            letterSpacing = 1.sp
                        )

                        Spacer(modifier = Modifier.height(24.dp))

                        Row(
                            modifier = Modifier.fillMaxWidth(),
                            horizontalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            ShoppitButton(
                                text = "Add Money",
                                onClick = onAddMoney,
                                containerColor = Color.White.copy(alpha = 0.2f),
                                contentColor = Color.White,
                                modifier = Modifier.weight(1f).height(48.dp)
                            )
                            ShoppitButton(
                                text = "Withdraw",
                                onClick = onWithdraw,
                                containerColor = Color.White.copy(alpha = 0.1f),
                                contentColor = Color.White,
                                modifier = Modifier.weight(1f).height(48.dp)
                            )
                        }
                    }
                }

                // Recent Transactions Header
                Text(
                    "Recent Transactions",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(start = 8.dp)
                )

                if (transactions.isEmpty()) {
                    Box(modifier = Modifier.weight(1f), contentAlignment = Alignment.Center) {
                        Column(horizontalAlignment = Alignment.CenterHorizontally) {
                            Icon(Icons.Default.History, contentDescription = null, modifier = Modifier.size(64.dp).alpha(0.1f))
                            Text("No transactions found", color = Color.Gray)
                        }
                    }
                } else {
                    LazyColumn(
                        state = listState,
                        modifier = Modifier.weight(1f),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        items(transactions) { tx ->
                            TransactionItemRow(tx)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun TransactionItemRow(tx: WalletTransaction) {
    Surface(
        modifier = Modifier.fillMaxWidth().shadow(2.dp, RoundedCornerShape(20.dp)),
        shape = RoundedCornerShape(20.dp),
        color = GlassWhite.copy(alpha = 0.8f)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            val isCredit = tx.type == "FUND_WALLET"

            Box(
                modifier = Modifier
                    .size(40.dp)
                    .clip(CircleShape)
                    .background(if (isCredit) Color(0xFFE8F5E9) else Color(0xFFFFEBEE)),
                contentAlignment = Alignment.Center
            ) {
                Icon(
                    imageVector = if (isCredit) Icons.Default.ArrowDownward else Icons.Default.ArrowUpward,
                    contentDescription = null,
                    tint = if (isCredit) ShoppitPrimary else Color.Red,
                    modifier = Modifier.size(20.dp)
                )
            }

            Spacer(modifier = Modifier.width(16.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(tx.narration, fontWeight = FontWeight.Bold, maxLines = 1)
                Text(tx.time, style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            }

            Text(
                text = "${if (isCredit) "+" else "-"}₦${formatDecimal(tx.amount, 0)}",
                fontWeight = FontWeight.Black,
                color = if (isCredit) ShoppitPrimary else Color.Red
            )
        }
    }
}
