package com.shoppitplus.shoppit.adapter

import android.content.Context
import android.graphics.Paint
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.shared.models.ProductDto

class ProductAdapter(
    private val products: List<ProductDto>,
    private val contextProvider: () -> Context,
    private val onProductClick: (ProductDto) -> Unit
) : RecyclerView.Adapter<ProductAdapter.ProductHolder>() {

    inner class ProductHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val productImage: ImageView = itemView.findViewById(R.id.productImage)
        val name: TextView = itemView.findViewById(R.id.productName)
        val oldPrice: TextView = itemView.findViewById(R.id.oldPrice)
        val newPrice: TextView = itemView.findViewById(R.id.newPrice)
        val addToCartBtn: ImageView = itemView.findViewById(R.id.addToCartBtn)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductHolder {
        val view = LayoutInflater.from(parent.context).inflate(R.layout.available_food, parent, false)
        return ProductHolder(view)
    }

    override fun onBindViewHolder(holder: ProductHolder, position: Int) {
        val product = products[position]
        val context = contextProvider()

        holder.name.text = product.name

        // Smart cast is impossible for public properties from a different module
        val discountPrice = product.discountPrice
        if (discountPrice != null && discountPrice < product.price) {
            holder.oldPrice.visibility = View.VISIBLE
            holder.oldPrice.text = "₦${discountPrice}"
            holder.oldPrice.paintFlags = holder.oldPrice.paintFlags or Paint.STRIKE_THRU_TEXT_FLAG
            holder.newPrice.text = "₦${product.price}"
        } else {
            holder.oldPrice.visibility = View.GONE
            holder.newPrice.text = "₦${product.price}"
        }

        // Use shared model image mapping
        val imageUrl = product.avatar?.firstOrNull()?.secureUrl

        Glide.with(context)
            .load(imageUrl)
            .placeholder(R.drawable.sample_food)
            .error(R.drawable.sample_food)
            .into(holder.productImage)

        // Click to open detail sheet
        holder.itemView.setOnClickListener { onProductClick(product) }
        holder.addToCartBtn.setOnClickListener { onProductClick(product) }
    }

    override fun getItemCount() = products.size
}
