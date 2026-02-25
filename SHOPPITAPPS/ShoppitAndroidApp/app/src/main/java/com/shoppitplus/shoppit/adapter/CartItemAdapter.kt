package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.shared.models.CartItem
import com.shoppitplus.shoppit.databinding.ItemCartProductSimpleBinding

class CartItemAdapter(private val items: List<CartItem>) : RecyclerView.Adapter<CartItemAdapter.ViewHolder>() {

    inner class ViewHolder(val binding: ItemCartProductSimpleBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemCartProductSimpleBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = items[position]
        with(holder.binding) {
            tvQuantity.text = "${item.quantity} ×"
            productName.text = item.product.name
            itemSubtotal.text = "₦${item.subtotal}"
        }
    }

    override fun getItemCount() = items.size
}
