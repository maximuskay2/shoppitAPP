package com.shoppitplus.shoppit

import android.os.Bundle
import android.util.Log
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.DividerItemDecoration
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.shoppitplus.shoppit.adapter.VendorProductsAdapter
import com.shoppitplus.shoppit.databinding.FragmentVendorProductsBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.Product
import com.shoppitplus.shoppit.utils.ProductDto
import com.shoppitplus.shoppit.utils.ToggleAvailabilityRequest
import kotlinx.coroutines.launch
import retrofit2.HttpException


class vendor_products : Fragment() {
   private var _binding: FragmentVendorProductsBinding? = null
    private val binding get() = _binding!!


    private lateinit var adapter: VendorProductsAdapter

    private var productsList: List<Product> = emptyList()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentVendorProductsBinding.inflate(inflater, container, false)


        setupRecyclerView()
        loadProducts()

        binding.swipeRefreshProducts.setOnRefreshListener {
            loadProducts()
        }

        binding.btnAddProduct.setOnClickListener {
            findNavController().navigate(R.id.action_vendor_products_to_addProduct)
        }

        binding.btnSelect.setOnClickListener {
            adapter.selectionMode = !adapter.selectionMode
            if (!adapter.selectionMode) {
                adapter.clearSelection()
                binding.bulkActionsBar.visibility = View.GONE
            }
            binding.btnSelect.text = if (adapter.selectionMode) "Done" else "Select"
        }

        adapter.onSelectionChanged = { ids ->
            binding.bulkActionsBar.visibility = if (ids.isEmpty()) View.GONE else View.VISIBLE
        }

        binding.btnBulkActivate.setOnClickListener { runBulkActivate() }
        binding.btnBulkDeactivate.setOnClickListener { runBulkDeactivate() }
        binding.btnBulkCancel.setOnClickListener {
            adapter.clearSelection()
            binding.bulkActionsBar.visibility = View.GONE
            binding.btnSelect.text = "Select"
        }

        return binding.root
    }

    private fun setupRecyclerView() {
        adapter = VendorProductsAdapter(
            onEdit = { product ->
                Toast.makeText(requireContext(), "Edit ${product.name}", Toast.LENGTH_SHORT).show()
            },
            onDelete = { product ->
                showDeleteConfirmation(product.id)
            },
            onShare = { product ->
                Toast.makeText(requireContext(), "Share ${product.name}", Toast.LENGTH_SHORT).show()
            },
            onToggleAvailability = { product, isActive ->
                toggleProductAvailability(product.id, isActive)
            },
            onDuplicate = { product ->
                duplicateProduct(product.id)
            }
        )

        binding.rvProducts.adapter = adapter
        binding.rvProducts.addItemDecoration(
            DividerItemDecoration(requireContext(), DividerItemDecoration.VERTICAL)
        )
    }

    private fun loadProducts() {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getVendorProducts()

                if (response.isSuccessful) {
                    val result = response.body()
                    if (result?.success == true) {
                        productsList = result.data ?: emptyList()   // ← .products, not .data.data

                        Log.d("VendorProducts", "Loaded ${productsList.size} products")
                        if (productsList.isNotEmpty()) {
                            Log.d("VendorProducts", "First: ${productsList[0].name}")
                        }

                        adapter.submitList(productsList)
                        binding.tvTotalProducts.text = "Total : ${productsList.size}"
                    } else {
                        showError(result?.message ?: "Failed to load products")
                    }
                } else {
                    showError("Server error: ${response.code()}")
                }
            } catch (e: HttpException) {
                showError("HTTP ${e.code()}: ${e.message()}")
            } catch (e: Exception) {
                showError(e.localizedMessage ?: "Something went wrong")
            } finally {
                showLoading(false)
                binding.swipeRefreshProducts.isRefreshing = false
            }
        }
    }

    private fun duplicateProduct(productId: String) {
        showLoading(true)
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).duplicateProduct(productId)
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Product duplicated", Toast.LENGTH_SHORT).show()
                    loadProducts()
                } else {
                    showError(response.body()?.message ?: "Duplicate failed")
                }
            } catch (e: Exception) {
                showError(e.localizedMessage ?: "Network error")
            } finally {
                showLoading(false)
            }
        }
    }

    private fun runBulkActivate() {
        val ids = adapter.selectedIds.toList()
        if (ids.isEmpty()) return
        showLoading(true)
        lifecycleScope.launch {
            ids.forEach { id -> toggleProductAvailabilitySync(id, true) }
            adapter.clearSelection()
            binding.bulkActionsBar.visibility = View.GONE
            binding.btnSelect.text = "Select"
            loadProducts()
            showLoading(false)
            Toast.makeText(requireContext(), "Activated ${ids.size} product(s)", Toast.LENGTH_SHORT).show()
        }
    }

    private fun runBulkDeactivate() {
        val ids = adapter.selectedIds.toList()
        if (ids.isEmpty()) return
        showLoading(true)
        lifecycleScope.launch {
            ids.forEach { id -> toggleProductAvailabilitySync(id, false) }
            adapter.clearSelection()
            binding.bulkActionsBar.visibility = View.GONE
            binding.btnSelect.text = "Select"
            loadProducts()
            showLoading(false)
            Toast.makeText(requireContext(), "Deactivated ${ids.size} product(s)", Toast.LENGTH_SHORT).show()
        }
    }

    private suspend fun toggleProductAvailabilitySync(productId: String, isAvailable: Boolean) {
        try {
            RetrofitClient.instance(requireContext()).toggleProductAvailability(
                productId,
                ToggleAvailabilityRequest(isAvailable)
            )
        } catch (_: Exception) { }
    }

    private fun deleteProduct(productId: String) {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).deleteProduct(productId)

                if (response.isSuccessful) {
                    val result = response.body()
                    if (result?.success == true) {
                        Toast.makeText(requireContext(), "Product deleted", Toast.LENGTH_SHORT).show()
                        // Remove from local list & refresh UI
                        productsList = productsList.filter { it.id != productId }
                        adapter.submitList(productsList as List<Product?>?)
                        binding.tvTotalProducts.text = "Total : ${productsList.size}"
                    } else {
                        showError(result?.message ?: "Delete failed")
                    }
                } else {
                    showError("Delete failed: ${response.code()}")
                }
            } catch (e: Exception) {
                showError(e.localizedMessage ?: "Network error")
            } finally {
                showLoading(false)
            }
        }
    }

    private fun toggleProductAvailability(productId: String, isAvailable: Boolean) {
        lifecycleScope.launch {
            try {
                // Simple JSON approach (if your backend supports it)
                val response = RetrofitClient.instance(requireContext()).toggleProductAvailability(
                    productId,
                    ToggleAvailabilityRequest(isAvailable)
                )

                /*
                val isAvailablePart = isAvailable.toString()
                    .toRequestBody("text/plain".toMediaTypeOrNull())

                val response = api.updateProduct(
                    productId = productId,
                    isAvailable = isAvailablePart
                )
                */

                if (response.isSuccessful) {
                    val result = response.body()
                    if (result?.success == true) {
                        // Optimistic update
                        productsList = productsList.map { product ->
                            if (product.id == productId) product.copy(isAvailable = isAvailable)
                            else product
                        }
                        adapter.submitList(productsList as List<Product?>?)
                        Toast.makeText(
                            requireContext(),
                            if (isAvailable) "Product activated" else "Product deactivated",
                            Toast.LENGTH_SHORT
                        ).show()
                    } else {
                        showError(result?.message ?: "Update failed")
                        // Revert toggle in UI
                        adapter.notifyItemChanged(
                            productsList.indexOfFirst { it.id == productId }
                        )
                    }
                } else {
                    showError("Update failed: ${response.code()}")
                }
            } catch (e: Exception) {
                showError(e.localizedMessage ?: "Network error")
            }
        }
    }

    private fun showDeleteConfirmation(productId: String) {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Delete Product")
            .setMessage("Are you sure you want to delete this product?\nThis action cannot be undone.")
            .setPositiveButton("Delete") { _, _ ->
                deleteProduct(productId)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showLoading(show: Boolean) {
        binding.loadingOverlay?.visibility = if (show) View.VISIBLE else View.GONE
        binding.progressBar?.visibility = if (show) View.VISIBLE else View.GONE
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}


