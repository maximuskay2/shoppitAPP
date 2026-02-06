package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemVendorProductBinding
import com.shoppitplus.shoppit.utils.Product

class VendorProductsAdapter(
    private val onEdit: (Product) -> Unit,
    private val onDelete: (Product) -> Unit,
    private val onShare: (Product) -> Unit,
    private val onToggleAvailability: (Product, Boolean) -> Unit
) : ListAdapter<Product, VendorProductsAdapter.ProductViewHolder>(ProductDiff()) {

    inner class ProductViewHolder(val binding: ItemVendorProductBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductViewHolder {
        val binding = ItemVendorProductBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ProductViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ProductViewHolder, position: Int) {
        val product = getItem(position)
        with(holder.binding) {

            tvProductName.text = product.name
            tvPrice.text = "₦${String.format("%,d", product.price)}"

            // Load first image
            product.avatar?.firstOrNull()?.let { url ->
                Glide.with(imgProduct.context)
                    .load(url)
                    .placeholder(R.drawable.bg_white_circle)
                    .centerCrop()
                    .into(imgProduct)
            }

            switchAvailable.isChecked = product.isAvailable

            // Actions
            tvEdit.setOnClickListener { onEdit(product) }
            tvDelete.setOnClickListener { onDelete(product) }
            tvShare.setOnClickListener { onShare(product) }

            switchAvailable.setOnCheckedChangeListener { _, isChecked ->
                onToggleAvailability(product, isChecked)
            }
        }
    }

    class ProductDiff : DiffUtil.ItemCallback<Product>() {
        override fun areItemsTheSame(old: Product, new: Product) = old.id == new.id
        override fun areContentsTheSame(old: Product, new: Product) = old == new
    }
}