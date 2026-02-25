package com.shoppitplus.shoppit.messaging

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemConversationRowBinding
import com.shoppitplus.shoppit.shared.models.ConversationDto

class ConversationAdapter(
    private var items: List<ConversationDto>,
    private val onItemClick: (ConversationDto) -> Unit
) : RecyclerView.Adapter<ConversationAdapter.ViewHolder>() {

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemConversationRowBinding.inflate(
            LayoutInflater.from(parent.context),
            parent,
            false
        )
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        holder.bind(items[position], onItemClick)
    }

    override fun getItemCount(): Int = items.size

    fun updateList(newItems: List<ConversationDto>) {
        items = newItems
        notifyDataSetChanged()
    }

    class ViewHolder(private val binding: ItemConversationRowBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(item: ConversationDto, onItemClick: (ConversationDto) -> Unit) {
            binding.txtName.text = item.other?.name ?: "Driver"
            binding.txtPreview.text = item.latestMessage?.content ?: "No messages yet"
            binding.root.setOnClickListener { onItemClick(item) }
        }
    }
}
