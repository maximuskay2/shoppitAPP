package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemOrderBinding
import com.shoppitplus.shoppit.utils.Order

class VendorOrderAdapter(
    private val onOrderClick: (Order) -> Unit  // ← Click listener
) : ListAdapter<Order, VendorOrderAdapter.OrderVH>(Diff()) {

    inner class OrderVH(
        private val binding: ItemOrderBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(order: Order) = with(binding) {
            // Bind data
            tvProduct.text = order.line_items.firstOrNull()?.product?.name ?: "Order"
            tvAmount.text = "₦${String.format("%,d", order.gross_total_amount)}"
            tvCustomer.text = order.receiver_name ?: order.user.name ?: "Customer"
            tvOrderId.text = "Order ID: ${order.tracking_id}"
            tvDate.text = order.created_at.take(16).replace("T", " · ")

            // Status styling
            tvStatus.text = order.status.replaceFirstChar { it.uppercase() }
            val statusBg = when (order.status.uppercase()) {
                "PENDING" -> R.drawable.bg_status_pending
                "PAID", "DISPATCHED" -> R.drawable.bg_status_paid
                "COMPLETED" -> R.drawable.bg_status_paid
                "CANCELLED" -> R.drawable.bg_status_failed
                else -> R.drawable.bg_status_failed
            }
            tvStatus.setBackgroundResource(statusBg)

            // Make entire item clickable
            root.setOnClickListener {
                onOrderClick(order)
            }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OrderVH {
        val binding = ItemOrderBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return OrderVH(binding)
    }

    override fun onBindViewHolder(holder: OrderVH, position: Int) {
        holder.bind(getItem(position))
    }

    class Diff : DiffUtil.ItemCallback<Order>() {
        override fun areItemsTheSame(oldItem: Order, newItem: Order) = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: Order, newItem: Order) = oldItem == newItem
    }
}