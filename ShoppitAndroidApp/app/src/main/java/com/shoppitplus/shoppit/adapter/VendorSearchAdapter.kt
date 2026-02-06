package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.utils.VendorDto

class VendorSearchAdapter :
    RecyclerView.Adapter<VendorSearchAdapter.VendorViewHolder>() {

    private val items = mutableListOf<VendorDto>()

    fun submitList(newItems: List<VendorDto>) {
        items.clear()
        items.addAll(newItems)
        notifyDataSetChanged()
    }

    inner class VendorViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val image: ImageView = view.findViewById(R.id.vendorImage)
        val name: TextView = view.findViewById(R.id.vendorName)
        val rating: TextView = view.findViewById(R.id.vendorRating)
        val address: TextView = view.findViewById(R.id.vendorAddress)
        val delivery: TextView = view.findViewById(R.id.vendorDelivery)
        val time: TextView = view.findViewById(R.id.vendorTime)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VendorViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_vendor_card, parent, false)
        return VendorViewHolder(view)
    }

    override fun onBindViewHolder(holder: VendorViewHolder, position: Int) {
        val vendor = items[position]

        holder.name.text = vendor.name
        holder.rating.text = vendor.average_rating.toString()
        holder.address.text = "${vendor.address}, ${vendor.city}"
        holder.delivery.text =
            if (vendor.delivery_fee == 0) "Free" else "From ₦${vendor.delivery_fee}"
        holder.time.text = vendor.approximate_shopping_time

        Glide.with(holder.image.context)
            .load(vendor.avatar)
            .placeholder(R.drawable.first_image)
            .into(holder.image)
    }

    override fun getItemCount() = items.size
}
