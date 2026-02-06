package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.utils.LineItem

class OrderItemsAdapter(private val items: List<LineItem>) :
    RecyclerView.Adapter<OrderItemsAdapter.ViewHolder>() {

    inner class ViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val imgProduct: ImageView = view.findViewById(R.id.imgProduct)
        val tvProductName: TextView = view.findViewById(R.id.tvProductName)
        val tvQuantityPrice: TextView = view.findViewById(R.id.tvQuantityPrice)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_order_line, parent, false)
        return ViewHolder(view)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val item = items[position].product
        val line = items[position]

        Glide.with(holder.imgProduct.context)
            .load(item.avatar?.firstOrNull())
            .placeholder(R.drawable.sample_food)
            .into(holder.imgProduct)

        holder.tvProductName.text = item.name
        holder.tvQuantityPrice.text = "×${line.quantity} • ₦${String.format("%,d", line.subtotal)}"
    }

    override fun getItemCount() = items.size
}