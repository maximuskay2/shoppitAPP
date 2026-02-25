package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material.icons.filled.FilterList
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.alpha
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.ProductDto
import com.shoppitplus.shoppit.shared.models.VendorDto
import com.shoppitplus.shoppit.shared.ui.components.ProductCard
import com.shoppitplus.shoppit.shared.ui.components.ShoppitTextField
import com.shoppitplus.shoppit.shared.ui.components.VendorCard
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SearchScreen(
    onBack: () -> Unit,
    onProductClick: (ProductDto) -> Unit,
    onVendorClick: (VendorDto) -> Unit
) {
    var query by remember { mutableStateOf("") }
    var isLoading by remember { mutableStateOf(false) }
    var searchType by remember { mutableStateOf(SearchType.PRODUCT) }

    // Mock data for UI development
    val products = remember { mutableStateListOf<ProductDto>() }
    val vendors = remember { mutableStateListOf<VendorDto>() }

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    Text(
                        "Search",
                        style = MaterialTheme.typography.headlineSmall,
                        fontWeight = FontWeight.Bold
                    )
                },
                navigationIcon = {
                    IconButton(onClick = onBack) {
                        Icon(Icons.Default.ArrowBack, contentDescription = "Back")
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(
                    containerColor = Color.Transparent
                )
            )
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(padding)
        ) {
            // Background blur circles
            Box(
                modifier = Modifier
                    .size(250.dp)
                    .offset(x = (-100).dp, y = 200.dp)
                    .blur(80.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.05f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp)
            ) {
                // Search Input with 3D shadow
                ShoppitTextField(
                    value = query,
                    onValueChange = { query = it },
                    label = "Find products or stores",
                    placeholder = "Search...",
                    leadingIcon = { Icon(Icons.Default.Search, contentDescription = null, tint = ShoppitPrimary) },
                    modifier = Modifier.padding(vertical = 8.dp)
                )

                Spacer(modifier = Modifier.height(16.dp))

                // Tabs for switching between Products and Vendors
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .clip(RoundedCornerShape(12.dp))
                        .background(GlassWhite.copy(alpha = 0.3f))
                        .padding(4.dp)
                ) {
                    SearchTabItem(
                        text = "Products",
                        isSelected = searchType == SearchType.PRODUCT,
                        onClick = { searchType = SearchType.PRODUCT },
                        modifier = Modifier.weight(1f)
                    )
                    SearchTabItem(
                        text = "Vendors",
                        isSelected = searchType == SearchType.VENDOR,
                        onClick = { searchType = SearchType.VENDOR },
                        modifier = Modifier.weight(1f)
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                // Filter Row
                Row(
                    modifier = Modifier.fillMaxWidth(),
                    horizontalArrangement = Arrangement.spacedBy(8.dp)
                ) {
                    FilterChip(
                        selected = false,
                        onClick = { /* Show Filter */ },
                        label = { Text("Price Range") },
                        leadingIcon = { Icon(Icons.Default.FilterList, contentDescription = null, modifier = Modifier.size(18.dp)) },
                        shape = RoundedCornerShape(12.dp)
                    )
                }

                Spacer(modifier = Modifier.height(16.dp))

                if (isLoading) {
                    Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                        CircularProgressIndicator(color = ShoppitPrimary)
                    }
                } else {
                    // Search results grid
                    AnimatedContent(targetState = searchType) { type ->
                        if (type == SearchType.PRODUCT) {
                            if (products.isEmpty() && query.isNotEmpty()) {
                                EmptySearchState()
                            } else {
                                LazyVerticalGrid(
                                    columns = GridCells.Fixed(2),
                                    contentPadding = PaddingValues(bottom = 16.dp)
                                ) {
                                    items(products) { product ->
                                        ProductCard(
                                            product = product,
                                            onClick = { onProductClick(product) },
                                            onAddToCart = { /* Add logic */ }
                                        )
                                    }
                                }
                            }
                        } else {
                            if (vendors.isEmpty() && query.isNotEmpty()) {
                                EmptySearchState()
                            } else {
                                LazyVerticalGrid(
                                    columns = GridCells.Fixed(1),
                                    contentPadding = PaddingValues(bottom = 16.dp),
                                    verticalArrangement = Arrangement.spacedBy(8.dp)
                                ) {
                                    items(vendors) { vendor ->
                                        VendorCard(vendor = vendor, onClick = { onVendorClick(vendor) })
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun SearchTabItem(
    text: String,
    isSelected: Boolean,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    val backgroundColor by animateColorAsState(
        targetValue = if (isSelected) ShoppitPrimary else Color.Transparent,
        animationSpec = tween(300)
    )
    val textColor by animateColorAsState(
        targetValue = if (isSelected) Color.White else ShoppitTextPrimary,
        animationSpec = tween(300)
    )

    Box(
        modifier = modifier
            .clip(RoundedCornerShape(10.dp))
            .background(backgroundColor)
            .clickable(onClick = onClick)
            .padding(vertical = 10.dp),
        contentAlignment = Alignment.Center
    ) {
        Text(
            text = text,
            color = textColor,
            fontWeight = if (isSelected) FontWeight.Bold else FontWeight.Medium,
            fontSize = 14.sp
        )
    }
}

@Composable
fun EmptySearchState() {
    Column(
        modifier = Modifier.fillMaxSize().padding(32.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(
            Icons.Default.Search,
            contentDescription = null,
            modifier = Modifier.size(80.dp).alpha(0.2f),
            tint = ShoppitTextPrimary
        )
        Spacer(modifier = Modifier.height(16.dp))
        Text(
            "No results found",
            style = MaterialTheme.typography.titleMedium,
            fontWeight = FontWeight.Bold,
            color = ShoppitTextPrimary.copy(alpha = 0.6f)
        )
        Text(
            "Try a different keyword or store name",
            style = MaterialTheme.typography.bodySmall,
            color = ShoppitTextPrimary.copy(alpha = 0.4f)
        )
    }
}

enum class SearchType {
    PRODUCT, VENDOR
}
