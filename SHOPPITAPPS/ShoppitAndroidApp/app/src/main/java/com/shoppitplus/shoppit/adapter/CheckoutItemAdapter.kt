package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemCheckoutItemBinding
import com.shoppitplus.shoppit.shared.models.CartItem

class CheckoutItemAdapter(
    private val items: List<CartItem>,
    private val onQuantityChange: (itemId: String, quantity: Int) -> Unit
) : RecyclerView.Adapter<CheckoutItemAdapter.ViewHolder>() {

    inner class ViewHolder(val binding: ItemCheckoutItemBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemCheckoutItemBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = items[position]

        with(holder.binding) {
            Glide.with(root.context)
                .load(item.product.avatar?.firstOrNull()?.secureUrl)
                .placeholder(R.drawable.sample_food)
                .into(itemImage)

            itemName.text = item.product.name
            itemPrice.text = "₦${item.price}"

            tvQuantity.text = item.quantity.toString()

            btnIncrease.setOnClickListener {
                onQuantityChange(item.id, item.quantity + 1)
            }

            btnDecrease.setOnClickListener {
                if (item.quantity > 1) {
                    onQuantityChange(item.id, item.quantity - 1)
                }
            }

            btnDeleteItem.setOnClickListener {
                onQuantityChange(item.id, 0)  // 0 = delete
            }
        }
    }

    override fun getItemCount() = items.size
}
