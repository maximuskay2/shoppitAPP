package com.shoppitplus.shoppit.shared.ui.components

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Star
import androidx.compose.material3.*
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.shoppitplus.shoppit.shared.models.VendorDto
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@Composable
fun VendorCard(
    vendor: VendorDto,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .width(280.dp)
            .padding(8.dp)
            .clickable(onClick = onClick)
            .shadow(4.dp, RoundedCornerShape(20.dp)),
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Column(modifier = Modifier.padding(12.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                // Vendor Avatar Placeholder
                Box(
                    modifier = Modifier
                        .size(50.dp)
                        .clip(CircleShape)
                        .background(Color.LightGray.copy(alpha = 0.2f)),
                    contentAlignment = Alignment.Center
                ) {
                    Text(vendor.name.take(1), fontWeight = FontWeight.Bold)
                }

                Spacer(modifier = Modifier.width(12.dp))

                Column {
                    Text(
                        text = vendor.name,
                        style = MaterialTheme.typography.titleMedium,
                        fontWeight = FontWeight.Bold,
                        color = ShoppitTextPrimary
                    )
                    Text(
                        text = vendor.businessName ?: "Verified Store",
                        style = MaterialTheme.typography.bodySmall,
                        color = Color.Gray
                    )
                }
            }

            Spacer(modifier = Modifier.height(12.dp))

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Row(verticalAlignment = Alignment.CenterVertically) {
                    Icon(
                        Icons.Default.Star,
                        contentDescription = null,
                        tint = Color(0xFFFFB300),
                        modifier = Modifier.size(16.dp)
                    )
                    Spacer(modifier = Modifier.width(4.dp))
                    Text(
                        text = vendor.averageRating.toString(),
                        style = MaterialTheme.typography.labelMedium,
                        fontWeight = FontWeight.Bold
                    )
                }

                Text(
                    text = if (vendor.deliveryFee == 0.0) "Free Delivery" else "₦${vendor.deliveryFee.toInt()} Delivery",
                    style = MaterialTheme.typography.labelSmall,
                    color = ShoppitPrimary,
                    fontWeight = FontWeight.SemiBold
                )
            }
        }
    }
}
