package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.shared.models.CartVendor
import com.shoppitplus.shoppit.databinding.ItemCartVendorChowdeckBinding

class CartVendorAdapter(
    private val vendors: List<CartVendor>,
    private val deliveryAddress: String,
    private val onVendorCheckout: (String) -> Unit,
    private val onClearVendor: (CartVendor) -> Unit
) : RecyclerView.Adapter<CartVendorAdapter.VendorViewHolder>() {

    inner class VendorViewHolder(val binding: ItemCartVendorChowdeckBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VendorViewHolder {
        val binding = ItemCartVendorChowdeckBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return VendorViewHolder(binding)
    }

    override fun onBindViewHolder(holder: VendorViewHolder, position: Int) {
        val cartVendor = vendors[position]
        val vendor = cartVendor.vendor

        with(holder.binding) {
            Glide.with(root.context)
                .load(vendor.avatar)
                .placeholder(R.drawable.sample_food)
                .error(R.drawable.sample_food)
                .into(vendorAvatar)

            vendorName.text = vendor.name
            vendorItemTotal.text = "${cartVendor.itemCount} Item${if (cartVendor.itemCount != 1) "s" else ""} • ₦${cartVendor.vendorTotal}"

            tvDeliveryAddress.text = "Delivering to $deliveryAddress"

            itemsRecyclerView.layoutManager = LinearLayoutManager(root.context)
            itemsRecyclerView.adapter = CartItemAdapter(cartVendor.items)

            var isExpanded = false
            tvViewSelection.setOnClickListener {
                isExpanded = !isExpanded
                itemsRecyclerView.visibility = if (isExpanded) View.VISIBLE else View.GONE
                tvViewSelection.text = if (isExpanded) "Hide Selection ⌃" else "View Selection ⌄"
            }

            btnCheckoutVendor.setOnClickListener {
                onVendorCheckout(cartVendor.vendor.id)
            }

            tvClearSelection.setOnClickListener {
                onClearVendor(cartVendor)
            }
        }
    }

    override fun getItemCount() = vendors.size
}
