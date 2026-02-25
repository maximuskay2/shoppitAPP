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
import com.shoppitplus.shoppit.shared.models.OrderDetail

class OrdersAdapter(
    private val onClick: (OrderDetail) -> Unit
) : ListAdapter<OrderDetail, OrdersAdapter.ViewHolder>(Diff()) {

    inner class ViewHolder(val binding: OrdersRowBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = OrdersRowBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val order = getItem(position)

        with(holder.binding) {
            val firstItem = order.lineItems.firstOrNull()
            if (firstItem != null) {
                Glide.with(root.context)
                    .load(firstItem.product?.avatar?.firstOrNull()?.secureUrl)
                    .placeholder(R.drawable.sample_food)
                    .into(productImage)

                productName.text = "${firstItem.productName ?: firstItem.product?.name} ×${firstItem.quantity}"
            }

            if (order.lineItems.size > 1) {
                tvMoreItems.visibility = View.VISIBLE
                tvMoreItems.text = "+ ${order.lineItems.size - 1} more item${if (order.lineItems.size > 2) "s" else ""}"
            } else {
                tvMoreItems.visibility = View.GONE
            }

            vendorName.text = "Shopitt Vendor"

            productPrice.text = "₦${String.format("%,d", order.grossTotalAmount.toInt())}"

            orderStatus.text = order.status
            val statusColor = when (order.status.uppercase()) {
                "PAID", "DELIVERED" -> R.color.primary_color
                "PENDING" -> R.color.primary_color_login
                else -> R.color.primary_color
            }
            orderStatus.setBackgroundColor(ContextCompat.getColor(root.context, statusColor))

            root.setOnClickListener { onClick(order) }
        }
    }

    class Diff : DiffUtil.ItemCallback<OrderDetail>() {
        override fun areItemsTheSame(old: OrderDetail, new: OrderDetail) = old.id == new.id
        override fun areContentsTheSame(old: OrderDetail, new: OrderDetail) = old == new
    }
}
