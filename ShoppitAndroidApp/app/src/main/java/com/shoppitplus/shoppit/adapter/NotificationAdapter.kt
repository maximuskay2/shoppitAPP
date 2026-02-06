package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemNotificationBinding
import com.shoppitplus.shoppit.utils.NotificationItem
import java.text.SimpleDateFormat
import java.util.*

class NotificationAdapter(
    private val onClick: (NotificationItem) -> Unit
) : ListAdapter<NotificationItem, NotificationAdapter.NotificationVH>(Diff()) {

    inner class NotificationVH(
        private val binding: ItemNotificationBinding
    ) : RecyclerView.ViewHolder(binding.root) {

        fun bind(item: NotificationItem) = with(binding) {
            // Title based on type
            val title = when (item.type) {
                "order-received" -> "New Order Received"
                "order-completed-vendor" -> "Payment Successful"
                "order-dispatched" -> "Order Dispatched"
                "admin-message" -> "New Message from Admin"
                else -> "Notification"
            }
            tvTitle.text = title

            // Body message
            val payload = item.data
            val body = when (item.type) {
                "order-received" -> {
                    "You have a new order from ${payload.customer_name ?: "a customer"}.\n" +
                            "Order #${payload.tracking_id} · ₦${payload.amount ?: 0}"
                }

                "order-completed-vendor" -> {
                    "₦${payload.vendor_amount ?: 0} has been credited to your wallet.\n" +
                            "Order #${payload.tracking_id}"
                }

                else -> "You have a new notification"
            }
            tvBody.text = body

            // Date
            tvDate.text = formatDate(item.created_at)

            // Type tag
            val tagRes = when (item.type) {
                "order-received" -> R.drawable.bg_tag_order
                "order-completed-vendor" -> R.drawable.bg_tag_payment
                "admin-message" -> R.drawable.bg_tag_admin
                else -> R.drawable.bg_tag_order
            }
            tagType.setBackgroundResource(tagRes)
            tagType.text = when (item.type) {
                "order-received" -> "Order"
                "order-completed-vendor" -> "Payment"
                "admin-message" -> "Admin"
                else -> "Delivery"
            }

            // Unread indicator
            binding.notificationCardContent.alpha = if (item.read_at == null) 1f else 0.7f
            viewUnreadIndicator.visibility = if (item.read_at == null) View.VISIBLE else View.GONE

            binding.notificationCardContent.setOnClickListener { onClick(item) }
        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): NotificationVH {
        val inflater = LayoutInflater.from(parent.context)
        // Temporarily switch to manual inflate → bypasses binding class
        val view = inflater.inflate(R.layout.item_notification, parent, false)
        val binding = ItemNotificationBinding.bind(view)  // bind instead of inflate
        return NotificationVH(binding)
    }

    override fun onBindViewHolder(holder: NotificationVH, position: Int) {
        holder.bind(getItem(position))
    }

    class Diff : DiffUtil.ItemCallback<NotificationItem>() {
        override fun areItemsTheSame(old: NotificationItem, new: NotificationItem) =
            old.id == new.id

        override fun areContentsTheSame(old: NotificationItem, new: NotificationItem) =
            old.read_at == new.read_at && old.updated_at == new.updated_at
    }

    private fun formatDate(dateString: String): String {
        return try {
            val input = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault())
            input.timeZone = TimeZone.getTimeZone("UTC")

            val date = input.parse(dateString.removeSuffix("Z"))!!
            val output = SimpleDateFormat("MMM dd, yyyy", Locale.getDefault())
            output.format(date)
        } catch (e: Exception) {
            dateString.take(10)
        }
    }

}