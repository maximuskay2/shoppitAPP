package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.OrdersRowBinding
import com.shoppitplus.shoppit.utils.Order

class OrdersAdapter(
    private val onClick: (Order) -> Unit
) : ListAdapter<Order, OrdersAdapter.ViewHolder>(Diff()) {

    inner class ViewHolder(val binding: OrdersRowBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = OrdersRowBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val order = getItem(position)

        with(holder.binding) {
            // First product image
            val firstItem = order.line_items.firstOrNull()
            if (firstItem != null) {
                Glide.with(root.context)
                    .load(firstItem.product.avatar?.firstOrNull())
                    .placeholder(R.drawable.sample_food)
                    .into(productImage)

                // Product name + quantity
                productName.text = "${firstItem.product.name} ×${firstItem.quantity}"
            }

            // Show "+ X more items" if more than 1
            if (order.line_items.size > 1) {
                tvMoreItems.visibility = View.VISIBLE
                tvMoreItems.text = "+ ${order.line_items.size - 1} more item${if (order.line_items.size > 2) "s" else ""}"
            } else {
                tvMoreItems.visibility = View.GONE
            }

            // Vendor
            vendorName.text = "Shopitt Vendor"


            // Total
            productPrice.text = "₦${String.format("%,d", order.net_total_amount)}"

            // Status with color
            orderStatus.text = order.status
            val statusColor = when (order.status) {
                "PAID", "DELIVERED" -> R.color.primary_color
                "PENDING" -> R.color.primary_color_login
                else -> R.color.primary_color
            }
            orderStatus.setBackgroundColor(ContextCompat.getColor(root.context, statusColor))

            root.setOnClickListener { onClick(order) }
        }
    }

    class Diff : DiffUtil.ItemCallback<Order>() {
        override fun areItemsTheSame(old: Order, new: Order) = old.id == new.id
        override fun areContentsTheSame(old: Order, new: Order) = old == new
    }
}