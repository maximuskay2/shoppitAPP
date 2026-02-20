package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.utils.Vendor
import com.shoppitplus.shoppit.databinding.ItemVendorHorizontalBinding

class VendorHorizontalAdapter(
    private val vendors: List<Vendor>,
    private val onVendorClick: (Vendor) -> Unit
) : RecyclerView.Adapter<VendorHorizontalAdapter.ViewHolder>() {

    inner class ViewHolder(val binding: ItemVendorHorizontalBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemVendorHorizontalBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val vendor = vendors[position]

        with(holder.binding) {
            Glide.with(root.context)
                .load(vendor.avatar)
                .placeholder(R.drawable.sample_food)
                .error(R.drawable.sample_food)
                .into(vendorAvatar)

            vendorName.text = vendor.name
            vendorLocation.text = "${vendor.address}, ${vendor.city}"

            val deliveryFee = if (vendor.deliveryFee == 0) "Free" else "From ₦${vendor.deliveryFee}"
            deliveryInfo.text = "$deliveryFee • ${vendor.approximateShoppingTime}"

            root.setOnClickListener { onVendorClick(vendor) }
        }
    }

    override fun getItemCount() = vendors.size
}