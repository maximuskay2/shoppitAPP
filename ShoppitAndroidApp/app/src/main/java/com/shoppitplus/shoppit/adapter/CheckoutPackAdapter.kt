package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.databinding.ItemCheckoutPackBinding
import com.shoppitplus.shoppit.utils.CartItemDetail

class CheckoutPackAdapter(
    private val items: List<CartItemDetail>,
    private val onQuantityChange: (itemId: String, quantity: Int) -> Unit
) : RecyclerView.Adapter<CheckoutPackAdapter.PackViewHolder>() {

    inner class PackViewHolder(val binding: ItemCheckoutPackBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PackViewHolder {
        val binding = ItemCheckoutPackBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return PackViewHolder(binding)
    }

    override fun onBindViewHolder(holder: PackViewHolder, position: Int) {
        with(holder.binding) {
            tvPackTitle.text = "Pack ${position + 1}"

            itemsInPackRecycler.layoutManager = LinearLayoutManager(root.context)
            itemsInPackRecycler.adapter = CheckoutItemAdapter(items) { itemId, qty ->
                onQuantityChange(itemId, qty)
            }
        }
    }

    override fun getItemCount() = 1
}