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
import androidx.compose.ui.draw.blur
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.ProductCategory
import com.shoppitplus.shoppit.shared.ui.components.BentoCard
import com.shoppitplus.shoppit.shared.ui.components.KineticHeadline
import com.shoppitplus.shoppit.shared.ui.components.ShoppitButton
import com.shoppitplus.shoppit.shared.ui.components.ShoppitTextField
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AddProductScreen(
    categories: List<ProductCategory>,
    onBack: () -> Unit,
    onAddCategory: () -> Unit,
    onCreateProduct: (name: String, price: String, categoryId: String, deliveryTime: String, discountPrice: String?, description: String?, isActive: Boolean) -> Unit
) {
    var name by remember { mutableStateOf("") }
    var price by remember { mutableStateOf("") }
    var deliveryTime by remember { mutableStateOf("") }
    var discountPrice by remember { mutableStateOf("") }
    var description by remember { mutableStateOf("") }
    var isActive by remember { mutableStateOf(true) }
    var selectedCategory by remember { mutableStateOf<ProductCategory?>(null) }
    var categoryExpanded by remember { mutableStateOf(false) }

    val scrollState = rememberScrollState()

    Scaffold(
        topBar = {
            TopAppBar(
                title = {
                    KineticHeadline(
                        text = "Add Product",
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
                    .offset(x = 200.dp, y = (-50).dp)
                    .blur(100.dp)
                    .clip(CircleShape)
                    .background(ShoppitPrimary.copy(alpha = 0.05f))
            )

            Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(scrollState)
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(24.dp)
            ) {
                // --- BENTO PHOTO SECTION ---
                Column {
                    Text(
                        "Product Showcase",
                        style = MaterialTheme.typography.labelLarge,
                        fontWeight = FontWeight.Black,
                        color = ShoppitTextPrimary.copy(alpha = 0.6f),
                        modifier = Modifier.padding(start = 8.dp, bottom = 12.dp)
                    )
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        repeat(2) { index ->
                            BentoCard(
                                modifier = Modifier
                                    .weight(1f)
                                    .aspectRatio(1f)
                                    .clickable { },
                                cornerRadius = 24.dp,
                                elevation = 8.dp
                            ) {
                                Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                                    Icon(
                                        Icons.Default.AddAPhoto,
                                        contentDescription = null,
                                        tint = ShoppitPrimary.copy(alpha = 0.4f),
                                        modifier = Modifier.size(32.dp)
                                    )
                                }
                            }
                        }
                    }
                }

                // --- BENTO FORM SECTIONS ---
                Text(
                    "Essential Details",
                    style = MaterialTheme.typography.labelLarge,
                    fontWeight = FontWeight.Black,
                    color = ShoppitTextPrimary.copy(alpha = 0.6f),
                    modifier = Modifier.padding(start = 8.dp)
                )

                BentoCard(modifier = Modifier.fillMaxWidth()) {
                    Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
                        ShoppitTextField(
                            value = name,
                            onValueChange = { name = it },
                            label = "Product Name",
                            placeholder = "e.g. Fresh Organic Tomatoes"
                        )

                        // Improved Category Bento Dropdown
                        ExposedDropdownMenuBox(
                            expanded = categoryExpanded,
                            onExpandedChange = { categoryExpanded = !categoryExpanded }
                        ) {
                            OutlinedTextField(
                                value = selectedCategory?.name ?: "Select Category",
                                onValueChange = {},
                                readOnly = true,
                                label = { Text("Category", fontWeight = FontWeight.Bold) },
                                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded = categoryExpanded) },
                                modifier = Modifier.fillMaxWidth().menuAnchor(),
                                shape = RoundedCornerShape(16.dp),
                                colors = OutlinedTextFieldDefaults.colors(
                                    focusedContainerColor = Color.Transparent,
                                    unfocusedContainerColor = Color.Transparent,
                                    unfocusedBorderColor = Color.LightGray.copy(alpha = 0.5f),
                                    focusedLabelColor = ShoppitPrimary
                                )
                            )
                            ExposedDropdownMenu(
                                expanded = categoryExpanded,
                                onDismissRequest = { categoryExpanded = false }
                            ) {
                                categories.forEach { category ->
                                    DropdownMenuItem(
                                        text = { Text(category.name) },
                                        onClick = {
                                            selectedCategory = category
                                            categoryExpanded = false
                                        }
                                    )
                                }
                                HorizontalDivider()
                                DropdownMenuItem(
                                    text = { Text("+ Create New Category", color = ShoppitPrimary, fontWeight = FontWeight.Bold) },
                                    onClick = {
                                        categoryExpanded = false
                                        onAddCategory()
                                    }
                                )
                            }
                        }
                    }
                }

                // Pricing Bento
                BentoCard(modifier = Modifier.fillMaxWidth()) {
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        ShoppitTextField(
                            value = price,
                            onValueChange = { price = it },
                            label = "Price (₦)",
                            modifier = Modifier.weight(1f)
                        )
                        ShoppitTextField(
                            value = discountPrice,
                            onValueChange = { discountPrice = it },
                            label = "Promo (Opt)",
                            modifier = Modifier.weight(1f)
                        )
                    }
                }

                // Description & Logistics Bento
                BentoCard(modifier = Modifier.fillMaxWidth()) {
                    Column(verticalArrangement = Arrangement.spacedBy(20.dp)) {
                        ShoppitTextField(
                            value = deliveryTime,
                            onValueChange = { deliveryTime = it },
                            label = "Delivery Estimation",
                            placeholder = "e.g. 20 - 30 mins"
                        )

                        ShoppitTextField(
                            value = description,
                            onValueChange = { description = it },
                            label = "Product Story",
                            placeholder = "Describe the quality and source..."
                        )

                        Row(
                            modifier = Modifier.fillMaxWidth().padding(horizontal = 4.dp),
                            verticalAlignment = Alignment.CenterVertically,
                            horizontalArrangement = Arrangement.SpaceBetween
                        ) {
                            Column {
                                Text("Market Ready", fontWeight = FontWeight.Bold)
                                Text("Show this product to customers", style = MaterialTheme.typography.bodySmall, color = Color.Gray)
                            }
                            Switch(
                                checked = isActive,
                                onCheckedChange = { isActive = it },
                                colors = SwitchDefaults.colors(checkedThumbColor = Color.White, checkedTrackColor = ShoppitPrimary)
                            )
                        }
                    }
                }

                ShoppitButton(
                    text = "Publish Product",
                    onClick = {
                        selectedCategory?.let {
                            onCreateProduct(name, price, it.id, deliveryTime, discountPrice.takeIf { it.isNotBlank() }, description.takeIf { it.isNotBlank() }, isActive)
                        }
                    },
                    modifier = Modifier.fillMaxWidth().height(56.dp),
                    enabled = name.isNotBlank() && price.isNotBlank() && selectedCategory != null
                )

                Spacer(modifier = Modifier.height(140.dp))
            }
        }
    }
}
