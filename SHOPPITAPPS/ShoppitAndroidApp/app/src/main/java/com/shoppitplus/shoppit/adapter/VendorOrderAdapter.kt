package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemOrderBinding
import com.shoppitplus.shoppit.shared.models.OrderDetail

class VendorOrderAdapter(
    private val onOrderClick: (OrderDetail) -> Unit
) : ListAdapter<OrderDetail, VendorOrderAdapter.OrderVH>(Diff()) {

    inner class OrderVH(
        private val binding: ItemOrderBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(order: OrderDetail) = with(binding) {
            tvProduct.text = order.lineItems.firstOrNull()?.product?.name ?: "Order"
            tvAmount.text = "₦${String.format("%,d", order.grossTotalAmount.toInt())}"
            tvCustomer.text = order.receiverName ?: order.user.name
            tvOrderId.text = "Order ID: ${order.trackingId}"
            tvDate.text = order.createdAt.take(16).replace("T", " · ")

            tvStatus.text = order.status.replaceFirstChar { it.uppercase() }
            val statusBg = when (order.status.uppercase()) {
                "PENDING" -> R.drawable.bg_status_pending
                "PAID", "DISPATCHED" -> R.drawable.bg_status_paid
                "COMPLETED" -> R.drawable.bg_status_paid
                "CANCELLED" -> R.drawable.bg_status_failed
                else -> R.drawable.bg_status_failed
            }
            tvStatus.setBackgroundResource(statusBg)

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

    class Diff : DiffUtil.ItemCallback<OrderDetail>() {
        override fun areItemsTheSame(oldItem: OrderDetail, newItem: OrderDetail) = oldItem.id == newItem.id
        override fun areContentsTheSame(oldItem: OrderDetail, newItem: OrderDetail) = oldItem == newItem
    }
}
