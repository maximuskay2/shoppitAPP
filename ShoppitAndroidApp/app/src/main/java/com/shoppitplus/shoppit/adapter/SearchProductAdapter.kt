package com.shoppitplus.shoppit.adapter

import android.app.Activity
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.AddToCartRequest
import com.shoppitplus.shoppit.utils.ProductDto
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch

class SearchProductAdapter(
    private val onCartUpdate: () -> Unit = {}
) : ListAdapter<ProductDto, SearchProductAdapter.ProductViewHolder>(ProductDiffCallback()) {

    private val cartQuantities = mutableMapOf<String, Int>()

    class ProductViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        val name: TextView = view.findViewById(R.id.productName)
        val price: TextView = view.findViewById(R.id.productPrice)
        val image: ImageView = view.findViewById(R.id.productImage)
        val addToCartBtn: View = view.findViewById(R.id.addToCart)
        val quantityLayout: View = view.findViewById(R.id.quantityLayout)
        val tvQuantity: TextView = view.findViewById(R.id.tvQuantity)
        val btnDecrease: View = view.findViewById(R.id.btnDecrease)
        val btnIncrease: View = view.findViewById(R.id.btnIncrease)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_product_card, parent, false)
        return ProductViewHolder(view)
    }

    override fun onBindViewHolder(holder: ProductViewHolder, position: Int) {
        val item = getItem(position)
        val productId = item.id

        holder.name.text = item.name
        holder.price.text = "₦ ${item.discount_price ?: item.price}"

        Glide.with(holder.image.context)
            .load(item.avatar?.firstOrNull())
            .placeholder(R.drawable.sample_food)
            .into(holder.image)

        val currentQty = cartQuantities[productId] ?: 0

        if (currentQty > 0) {
            holder.addToCartBtn.visibility = View.GONE
            holder.quantityLayout.visibility = View.VISIBLE
            holder.tvQuantity.text = currentQty.toString()
        } else {
            holder.addToCartBtn.visibility = View.VISIBLE
            holder.quantityLayout.visibility = View.GONE
        }

        holder.addToCartBtn.setOnClickListener {
            updateCart(productId, 1, holder)
        }

        holder.btnIncrease.setOnClickListener {
            updateCart(productId, currentQty + 1, holder)
        }

        holder.btnDecrease.setOnClickListener {
            if (currentQty > 1) {
                updateCart(productId, currentQty - 1, holder)
            } else {
                updateCart(productId, 0, holder)
            }
        }
    }

    private fun updateCart(productId: String, newQty: Int, holder: ProductViewHolder) {
        CoroutineScope(Dispatchers.Main).launch {
            try {
                val response = RetrofitClient.instance(holder.itemView.context)
                    .addToCart(AddToCartRequest(productId, newQty))

                if (response.success) {
                    if (newQty > 0) {
                        cartQuantities[productId] = newQty
                    } else {
                        cartQuantities.remove(productId)
                    }
                    notifyItemChanged(holder.adapterPosition) // Update this item only
                    TopBanner.showSuccess(holder.itemView.context as Activity, "Cart updated")
                    onCartUpdate()
                } else {
                    TopBanner.showError(holder.itemView.context as Activity, response.message ?: "Failed")
                }
            } catch (e: Exception) {
                TopBanner.showError(holder.itemView.context as Activity, "Network error")
            }
        }
    }
}

class ProductDiffCallback : DiffUtil.ItemCallback<ProductDto>() {
    override fun areItemsTheSame(oldItem: ProductDto, newItem: ProductDto): Boolean =
        oldItem.id == newItem.id

    override fun areContentsTheSame(oldItem: ProductDto, newItem: ProductDto): Boolean =
        oldItem == newItem
}