package com.shoppitplus.shoppit.messaging

import android.view.Gravity
import android.view.LayoutInflater
import android.view.ViewGroup
import android.widget.FrameLayout
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemMessageBinding
import com.shoppitplus.shoppit.shared.models.MessageDto

class MessageAdapter(
    private var items: List<MessageDto>
) : RecyclerView.Adapter<MessageAdapter.ViewHolder>() {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemMessageBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount(): Int = items.size

    fun updateList(newItems: List<MessageDto>) {
        items = newItems
        notifyDataSetChanged()
    }

    fun appendMessage(message: MessageDto) {
        items = items + message
        notifyItemInserted(items.size - 1)
    }

    class ViewHolder(private val binding: ItemMessageBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(item: MessageDto) {
            binding.txtContent.text = item.content
            binding.txtSender.text = item.senderName ?: "Unknown"
            val params = binding.bubbleContainer.layoutParams as FrameLayout.LayoutParams
            params.gravity = if (item.isMine) Gravity.END else Gravity.START
            binding.bubbleContainer.layoutParams = params
            binding.bubbleContainer.setBackgroundResource(
                if (item.isMine) R.drawable.bg_message_bubble_mine else R.drawable.bg_message_bubble
            )
        }
    }
}
