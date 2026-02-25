package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.DiffUtil
import androidx.recyclerview.widget.ListAdapter
import androidx.recyclerview.widget.RecyclerView
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemVendorProductBinding
import com.shoppitplus.shoppit.shared.models.ProductDto

class VendorProductsAdapter(
    private val onEdit: (ProductDto) -> Unit,
    private val onDelete: (ProductDto) -> Unit,
    private val onShare: (ProductDto) -> Unit,
    private val onToggleAvailability: (ProductDto, Boolean) -> Unit,
    private val onDuplicate: (ProductDto) -> Unit
) : ListAdapter<ProductDto, VendorProductsAdapter.ProductViewHolder>(ProductDiff()) {

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
            checkProduct.isChecked = selectedIds.contains(product.id ?: "")
            checkProduct.setOnCheckedChangeListener { _, isChecked ->
                val id = product.id ?: ""
                val newSet = if (isChecked) selectedIds + id else selectedIds - id
                selectedIds = newSet
            }

            tvProductName.text = product.name
            tvPrice.text = "₦${String.format("%,d", product.price.toInt())}"

            product.avatar?.firstOrNull()?.secureUrl?.let { url ->
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

    class ProductDiff : DiffUtil.ItemCallback<ProductDto>() {
        override fun areItemsTheSame(old: ProductDto, new: ProductDto) = old.id == new.id
        override fun areContentsTheSame(old: ProductDto, new: ProductDto) = old == new
    }
}
