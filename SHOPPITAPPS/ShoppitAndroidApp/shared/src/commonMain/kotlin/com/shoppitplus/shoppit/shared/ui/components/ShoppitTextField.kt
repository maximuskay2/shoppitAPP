package com.shoppitplus.shoppit.shared.ui.components

import androidx.compose.animation.animateColorAsState
import androidx.compose.animation.core.animateDpAsState
import androidx.compose.animation.core.tween
import androidx.compose.foundation.background
import androidx.compose.foundation.interaction.MutableInteractionSource
import androidx.compose.foundation.interaction.collectIsFocusedAsState
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.shadow
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import com.shoppitplus.shoppit.shared.ui.theme.GlassWhite
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitPrimary
import com.shoppitplus.shoppit.shared.ui.theme.ShoppitTextPrimary

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ShoppitTextField(
    value: String,
    onValueChange: (String) -> Unit,
    label: String,
    modifier: Modifier = Modifier,
    placeholder: String? = null,
    isError: Boolean = false,
    visualTransformation: VisualTransformation = VisualTransformation.None,
    keyboardOptions: KeyboardOptions = KeyboardOptions.Default,
    leadingIcon: @Composable (() -> Unit)? = null,
    trailingIcon: @Composable (() -> Unit)? = null
) {
    val interactionSource = remember { MutableInteractionSource() }
    val isFocused by interactionSource.collectIsFocusedAsState()

    // Animate border and shadow for 3D depth
    val borderThickness by animateDpAsState(
        targetValue = if (isFocused) 2.dp else 1.dp,
        animationSpec = tween(300)
    )
    val elevation by animateDpAsState(
        targetValue = if (isFocused) 8.dp else 2.dp,
        animationSpec = tween(300)
    )
    val borderColor by animateColorAsState(
        targetValue = when {
            isError -> MaterialTheme.colorScheme.error
            isFocused -> ShoppitPrimary
            else -> Color.LightGray.copy(alpha = 0.5f)
        }
    )

    Column(modifier = modifier.fillMaxWidth()) {
        Text(
            text = label,
            style = MaterialTheme.typography.labelLarge,
            color = if (isError) MaterialTheme.colorScheme.error else ShoppitTextPrimary,
            modifier = Modifier.padding(bottom = 8.dp, start = 4.dp)
        )

        OutlinedTextField(
            value = value,
            onValueChange = onValueChange,
            modifier = Modifier
                .fillMaxWidth()
                .shadow(
                    elevation = elevation,
                    shape = RoundedCornerShape(16.dp),
                    spotColor = borderColor.copy(alpha = 0.2f)
                )
                .background(
                    color = GlassWhite.copy(alpha = 0.9f),
                    shape = RoundedCornerShape(16.dp)
                ),
            placeholder = placeholder?.let { { Text(it) } },
            isError = isError,
            visualTransformation = visualTransformation,
            keyboardOptions = keyboardOptions,
            leadingIcon = leadingIcon,
            trailingIcon = trailingIcon,
            shape = RoundedCornerShape(16.dp),
            colors = OutlinedTextFieldDefaults.colors(
                focusedBorderColor = borderColor,
                unfocusedBorderColor = borderColor,
                errorBorderColor = MaterialTheme.colorScheme.error,
                focusedContainerColor = Color.Transparent,
                unfocusedContainerColor = Color.Transparent,
                focusedLabelColor = ShoppitPrimary,
                unfocusedLabelColor = ShoppitTextPrimary.copy(alpha = 0.6f)
            ),
            interactionSource = interactionSource,
            singleLine = true
        )
    }
}
