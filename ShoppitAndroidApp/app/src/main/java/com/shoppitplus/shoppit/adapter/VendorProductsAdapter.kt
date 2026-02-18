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
    private val onToggleAvailability: (Product, Boolean) -> Unit,
    private val onDuplicate: (Product) -> Unit
) : ListAdapter<Product, VendorProductsAdapter.ProductViewHolder>(ProductDiff()) {

    var selectionMode: Boolean = false
        set(value) {
            if (field != value) {
                field = value
                notifyDataSetChanged()
            }
        }

    var selectedIds: Set<String> = emptySet()
        set(value) {
            field = value
            onSelectionChanged?.invoke(value)
        }

    var onSelectionChanged: ((Set<String>) -> Unit)? = null

    fun clearSelection() {
        selectionMode = false
        selectedIds = emptySet()
        notifyDataSetChanged()
    }

    inner class ProductViewHolder(val binding: ItemVendorProductBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ProductViewHolder {
        val binding = ItemVendorProductBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ProductViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ProductViewHolder, position: Int) {
        val product = getItem(position)
        with(holder.binding) {

            checkProduct.visibility = if (selectionMode) android.view.View.VISIBLE else android.view.View.GONE
            checkProduct.isChecked = selectedIds.contains(product.id)
            checkProduct.setOnCheckedChangeListener { _, isChecked ->
                val newSet = if (isChecked) selectedIds + product.id else selectedIds - product.id
                selectedIds = newSet
            }

            tvProductName.text = product.name
            tvPrice.text = "₦${String.format("%,d", product.price)}"

            product.avatar?.firstOrNull()?.let { url ->
                Glide.with(imgProduct.context)
                    .load(url)
                    .placeholder(R.drawable.bg_white_circle)
                    .centerCrop()
                    .into(imgProduct)
            }

            switchAvailable.isChecked = product.isAvailable

            tvEdit.setOnClickListener { onEdit(product) }
            tvDelete.setOnClickListener { onDelete(product) }
            tvShare.setOnClickListener { onShare(product) }
            tvDuplicate.setOnClickListener { onDuplicate(product) }

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