package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyRow
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Notifications
import androidx.compose.material.icons.filled.Search
import androidx.compose.material.icons.filled.LocalFireDepartment
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
import com.shoppitplus.shoppit.shared.models.VendorDto
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ProductCard
import com.shoppitplus.shoppit.shared.ui.components.VendorCard
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun HomeScreen(
    vendors: List<VendorDto>,
    products: List<ProductDto>,
    onProductClick: (ProductDto) -> Unit,
    onVendorClick: (VendorDto) -> Unit,
    onSearchClick: () -> Unit,
    onNotificationsClick: () -> Unit
) {
    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "Shoppit",
                        scrollOffset = scrollState.value,
                        threshold = 200
                    )
                },
                actions = {
                    IconButton(onClick = onSearchClick) {
                        Icon(Icons.Default.Search, contentDescription = "Search")
                    }
                    IconButton(onClick = onNotificationsClick) {
                        Icon(Icons.Default.Notifications, contentDescription = "Notifications")
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
            // Background blur circles for 3D depth
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = 200.dp, y = 100.dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.05f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(horizontal = 16.dp)
            ) {
                Spacer(modifier = Modifier.height(16.dp))

                // --- BENTO HERO SECTION ---
                BentoCard(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(200.dp),
                    backgroundColor = ShoppitPrimary
                ) {
                    Column(
                        modifier = Modifier.fillMaxSize(),
                        verticalArrangement = Arrangement.Center,
                        horizontalAlignment = Alignment.CenterHorizontally
                    ) {
                        Text(
                            "Flash Sales!",
                            color = Color.White,
                            style = MaterialTheme.typography.displaySmall,
                            fontWeight = FontWeight.Black
                        )
                        Text(
                            "Up to 50% OFF",
                            color = Color.White.copy(alpha = 0.8f),
                            style = MaterialTheme.typography.titleMedium
                        )
                        Spacer(modifier = Modifier.height(16.dp))
                        Button(
                            onClick = {},
                            colors = ButtonDefaults.buttonColors(containerColor = Color.White, contentColor = ShoppitPrimary),
                            shape = RoundedCornerShape(12.dp)
                        ) {
                            Text("Shop Now", fontWeight = FontWeight.Bold)
                        }
                    }
                }

                Spacer(modifier = Modifier.height(20.dp))

                // --- BENTO ROW (Nearby Vendors) ---
                Text(
                    "Nearby Stores",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(start = 8.dp, bottom = 12.dp)
                )

                LazyRow(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(12.dp)
                ) {
                    items(vendors) { vendor ->
                        VendorCard(vendor = vendor, onClick = { onVendorClick(vendor) })
                    }
                }

                Spacer(modifier = Modifier.height(32.dp))

                // --- STAGGERED BENTO DISCOVERY ---
                Text(
                    "Discovery Grid",
                    style = MaterialTheme.typography.titleMedium,
                    fontWeight = FontWeight.Bold,
                    modifier = Modifier.padding(start = 8.dp, bottom = 12.dp)
                )

                Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                    // Left Column (Big Item)
                    if (products.isNotEmpty()) {
                        Box(modifier = Modifier.weight(1f)) {
                            ProductCard(
                                product = products[0],
                                onClick = { onProductClick(products[0]) },
                                onAddToCart = {}
                            )
                        }
                    }

                    // Right Column (Stacked Small Items)
                    Column(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(16.dp)) {
                        if (products.size > 1) {
                            BentoCard(
                                modifier = Modifier.fillMaxWidth().height(100.dp),
                                backgroundColor = Color(0xFFFFAB00).copy(alpha = 0.1f)
                            ) {
                                Row(verticalAlignment = Alignment.CenterVertically) {
                                    Icon(Icons.Default.LocalFireDepartment, contentDescription = null, tint = Color(0xFFFF6D00))
                                    Spacer(modifier = Modifier.width(8.dp))
                                    Text("Hot Deals", fontWeight = FontWeight.Bold)
                                }
                            }
                        }
                        if (products.size > 2) {
                            ProductCard(
                                product = products[2],
                                onClick = { onProductClick(products[2]) },
                                onAddToCart = {}
                            )
                        }
                    }
                }

                Spacer(modifier = Modifier.height(100.dp))
            }
        }
    }
}
