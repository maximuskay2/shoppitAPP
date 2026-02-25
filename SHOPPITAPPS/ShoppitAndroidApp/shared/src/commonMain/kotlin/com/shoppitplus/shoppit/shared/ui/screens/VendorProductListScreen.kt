package com.shoppitplus.shoppit.shared.ui.screens

import androidx.compose.animation.*
import androidx.compose.animation.core.*
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
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
fun VendorProductListScreen(
    products: List<ProductDto>,
    onBack: () -> Unit,
    onAddProduct: () -> Unit,
    onEditProduct: (ProductDto) -> Unit,
    onDeleteProduct: (String) -> Unit,
    onToggleAvailability: (String, Boolean) -> Unit
) {
    var isSelectionMode by remember { mutableStateOf(false) }
    val selectedIds = remember { mutableStateListOf<String>() }
    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "My Products",
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
                    TextButton(onClick = {
                        isSelectionMode = !isSelectionMode
                        if (!isSelectionMode) selectedIds.clear()
                    }) {
                        Text(if (isSelectionMode) "Done" else "Select", color = ShoppitPrimary, fontWeight = FontWeight.Bold)
                    }
                },
                colors = TopAppBarDefaults.topAppBarColors(containerColor = Color.Transparent)
            )
        },
        floatingActionButton = {
            if (!isSelectionMode) {
                ExtendedFloatingActionButton(
                    onClick = onAddProduct,
                    containerColor = ShoppitPrimary,
                    contentColor = Color.White,
                    shape = RoundedCornerShape(20.dp),
                    elevation = FloatingActionButtonDefaults.elevation(defaultElevation = 8.dp),
                    icon = { Icon(Icons.Default.Add, contentDescription = null) },
                    text = { Text("Add Product", fontWeight = FontWeight.Bold) }
                )
            }
        }
    ) { padding ->
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(MaterialTheme.colorScheme.background)
                .padding(padding)
        ) {
            // Background blur for depth
            Box(
                modifier = Modifier
                    .size(300.dp)
                    .offset(x = (-100).dp, y = 300.dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(Color(0xFF004D40).copy(alpha = 0.05f))
            )

            if (products.isEmpty()) {
                EmptyProductState()
            } else {
                Column {
                    // Bulk Action Bento Bar
                    AnimatedVisibility(
                        visible = isSelectionMode && selectedIds.isNotEmpty(),
                        enter = slideInVertically { it } + fadeIn(),
                        exit = slideOutVertically { it } + fadeOut()
                    ) {
                        BentoCard(
                            modifier = Modifier.fillMaxWidth().padding(16.dp),
                            backgroundColor = ShoppitPrimary,
                            elevation = 12.dp
                        ) {
                            Row(
                                modifier = Modifier.fillMaxWidth(),
                                horizontalArrangement = Arrangement.SpaceBetween,
                                verticalAlignment = Alignment.CenterVertically
                            ) {
                                Text("${selectedIds.size} selected", color = Color.White, fontWeight = FontWeight.Black)
                                Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                                    IconButton(onClick = { /* Bulk Activate */ }) {
                                        Icon(Icons.Default.CheckCircle, contentDescription = "Activate", tint = Color.White)
                                    }
                                    IconButton(onClick = { /* Bulk Deactivate */ }) {
                                        Icon(Icons.Default.Block, contentDescription = "Deactivate", tint = Color.White)
                                    }
                                }
                            }
                        }
                    }

                    LazyColumn(
                        modifier = Modifier.fillMaxSize(),
                        contentPadding = PaddingValues(16.dp),
                        verticalArrangement = Arrangement.spacedBy(16.dp)
                    ) {
                        items(products) { product ->
                            val id = product.id ?: ""
                            VendorProductBentoItem(
                                product = product,
                                isSelected = selectedIds.contains(id),
                                isSelectionMode = isSelectionMode,
                                onSelect = {
                                    if (selectedIds.contains(id)) selectedIds.remove(id)
                                    else selectedIds.add(id)
                                },
                                onEdit = { onEditProduct(product) },
                                onDelete = { onDeleteProduct(id) },
                                onToggle = { onToggleAvailability(id, it) }
                            )
                        }

                        item { Spacer(modifier = Modifier.height(100.dp)) }
                    }
                }
            }
        }
    }
}

@Composable
fun VendorProductBentoItem(
    product: ProductDto,
    isSelected: Boolean,
    isSelectionMode: Boolean,
    onSelect: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit,
    onToggle: (Boolean) -> Unit
) {
    BentoCard(
        modifier = Modifier
            .fillMaxWidth()
            .clickable { if (isSelectionMode) onSelect() else onEdit() },
        backgroundColor = if (isSelected) ShoppitPrimary.copy(alpha = 0.08f) else GlassWhite.copy(alpha = 0.8f),
        elevation = if (isSelected) 8.dp else 2.dp
    ) {
        Row(
            verticalAlignment = Alignment.CenterVertically
        ) {
            if (isSelectionMode) {
                Checkbox(
                    checked = isSelected,
                    onCheckedChange = { onSelect() },
                    colors = CheckboxDefaults.colors(checkedColor = ShoppitPrimary)
                )
                Spacer(modifier = Modifier.width(12.dp))
            }

            // Image Slot with 3D shadow
            Surface(
                modifier = Modifier.size(64.dp).shadow(4.dp, RoundedCornerShape(16.dp)),
                shape = RoundedCornerShape(16.dp),
                color = Color.White.copy(alpha = 0.5f)
            ) {
                Box(contentAlignment = Alignment.Center) {
                    Icon(Icons.Default.Image, contentDescription = null, tint = Color.Gray.copy(alpha = 0.3f))
                }
            }

            Spacer(modifier = Modifier.width(16.dp))

            Column(modifier = Modifier.weight(1f)) {
                Text(
                    text = product.name,
                    fontWeight = FontWeight.Bold,
                    style = MaterialTheme.typography.bodyLarge,
                    color = ShoppitTextPrimary
                )
                Text(
                    text = "₦${formatCurrency(product.price)}",
                    color = ShoppitPrimary,
                    fontWeight = FontWeight.Black,
                    fontSize = 16.sp
                )
            }

            Column(horizontalAlignment = Alignment.End) {
                Switch(
                    checked = product.isAvailable,
                    onCheckedChange = onToggle,
                    colors = SwitchDefaults.colors(
                        checkedThumbColor = Color.White,
                        checkedTrackColor = ShoppitPrimary
                    )
                )
                if (!isSelectionMode) {
                    IconButton(onClick = onDelete, modifier = Modifier.size(24.dp)) {
                        Icon(
                            Icons.Default.DeleteOutline,
                            contentDescription = "Delete",
                            tint = Color.Red.copy(alpha = 0.5f),
                            modifier = Modifier.size(20.dp)
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun EmptyProductState() {
    Column(
        modifier = Modifier.fillMaxSize(),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.Center
    ) {
        Icon(Icons.Default.Inventory2, contentDescription = null, modifier = Modifier.size(100.dp).alpha(0.05f))
        Spacer(modifier = Modifier.height(16.dp))
        Text("Your store is empty", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold, color = Color.Gray)
        Text("Start adding products to reach customers", style = MaterialTheme.typography.bodySmall, color = Color.Gray.copy(alpha = 0.7f))
    }
}
