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
import com.shoppitplus.shoppit.shared.models.ProductDto
import com.shoppitplus.shoppit.shared.models.ToggleAvailabilityRequest
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.launch

class vendor_products : Fragment() {
    private var _binding: FragmentVendorProductsBinding? = null
    private val binding get() = _binding!!

    private lateinit var adapter: VendorProductsAdapter
    private var productsList: List<ProductDto> = emptyList()
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorProductsBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())

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
                product.id?.let { showDeleteConfirmation(it) }
            },
            onShare = { product ->
                Toast.makeText(requireContext(), "Share ${product.name}", Toast.LENGTH_SHORT).show()
            },
            onToggleAvailability = { product, isActive ->
                product.id?.let { toggleProductAvailability(it, isActive) }
            },
            onDuplicate = { product ->
                product.id?.let { duplicateProduct(it) }
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
                val response = apiClient.getVendorProducts(authToken!!)

                if (response.success) {
                    productsList = response.data ?: emptyList()
                    adapter.submitList(productsList)
                    binding.tvTotalProducts.text = "Total : ${productsList.size}"
                } else {
                    showError(response.message)
                }
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
                val response = apiClient.duplicateProduct(authToken!!, productId)
                if (response.success) {
                    Toast.makeText(requireContext(), "Product duplicated", Toast.LENGTH_SHORT).show()
                    loadProducts()
                } else {
                    showError(response.message)
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
            apiClient.toggleProductAvailability(authToken!!, productId, ToggleAvailabilityRequest(isAvailable))
        } catch (_: Exception) { }
    }

    private fun deleteProduct(productId: String) {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = apiClient.deleteProduct(authToken!!, productId)

                if (response.success) {
                    Toast.makeText(requireContext(), "Product deleted", Toast.LENGTH_SHORT).show()
                    productsList = productsList.filter { it.id != productId }
                    adapter.submitList(productsList)
                    binding.tvTotalProducts.text = "Total : ${productsList.size}"
                } else {
                    showError(response.message)
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
                val response = apiClient.toggleProductAvailability(authToken!!, productId, ToggleAvailabilityRequest(isAvailable))

                if (response.success) {
                    productsList = productsList.map { product ->
                        if (product.id == productId) product.copy(isAvailable = isAvailable)
                        else product
                    }
                    adapter.submitList(productsList)
                    Toast.makeText(
                        requireContext(),
                        if (isAvailable) "Product activated" else "Product deactivated",
                        Toast.LENGTH_SHORT
                    ).show()
                } else {
                    showError(response.message)
                    adapter.notifyItemChanged(productsList.indexOfFirst { it.id == productId })
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
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
    }

    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
