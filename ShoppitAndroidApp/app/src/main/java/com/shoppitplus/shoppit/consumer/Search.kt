package com.shoppitplus.shoppit.consumer

import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.inputmethod.EditorInfo
import android.widget.ArrayAdapter
import android.widget.ImageView
import android.widget.ListView
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.core.widget.addTextChangedListener
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.GridLayoutManager
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.SearchProductAdapter
import com.shoppitplus.shoppit.adapter.VendorSearchAdapter
import com.shoppitplus.shoppit.databinding.FragmentSearchBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch



class Search : Fragment() {

    enum class SearchType {
        PRODUCT,
        VENDOR
    }

    private var _binding: FragmentSearchBinding? = null
    private val binding get() = _binding!!

    private lateinit var adapter: SearchProductAdapter
    private var searchJob: Job? = null
    private val debounceDelay = 400L
    private var minPrice: Int? = null
    private var maxPrice: Int? = null

    private var currentSearchType = SearchType.PRODUCT

    private lateinit var productAdapter: SearchProductAdapter
    private lateinit var vendorAdapter: VendorSearchAdapter

    private lateinit var productLayoutManager: GridLayoutManager
    private lateinit var vendorLayoutManager: GridLayoutManager



    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentSearchBinding.inflate(inflater, container, false)

        setupRecycler()
        setupSearchInput()
        setupFilters()

        binding.recyclerProducts.layoutManager =
            GridLayoutManager(requireContext(), 2)


        return binding.root
    }

    private fun setupRecycler() {
        productAdapter = SearchProductAdapter()
        vendorAdapter = VendorSearchAdapter()

        binding.recyclerProducts.layoutManager = GridLayoutManager(requireContext(), 2)
        binding.recyclerProducts.adapter = productAdapter  // Default to products
    }



    private fun setupSearchInput() {
        binding.searchInput.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEARCH) {
                performSearch(binding.searchInput.text.toString())
                true
            } else false
        }
        binding.searchInput.addTextChangedListener {
            val query = it.toString().trim()

            searchJob?.cancel()
            searchJob = lifecycleScope.launch {
                delay(debounceDelay)

                if (query.length >= 2) {
                    performSearch(query)
                } else {
                    clearResults()
                    showEmptyState(true)
                }
            }
        }

    }

    private fun showEmptyState(show: Boolean) {
        binding.emptyState.visibility = if (show) View.VISIBLE else View.GONE
    }


    private fun setupFilters() {
        binding.priceFilter.chipText.text =
            binding.priceFilter.root.tag?.toString() ?: "Price"

        binding.vendorFilter.chipText.text =
            binding.vendorFilter.root.tag?.toString() ?: "Vendor"

        binding.priceFilter.root.setOnClickListener {
            currentSearchType = SearchType.PRODUCT
            showPriceFilter()
        }

    }

    private fun performSearch(query: String) {
        lifecycleScope.launch {
            try {
                showLoading(true)
                showEmptyState(false)

                val api = RetrofitClient.instance(requireContext())

                // Search products
                val productResponse = api.searchProducts(query)

                if (productResponse.success &&
                    productResponse.data?.data?.isNotEmpty() == true) {

                    currentSearchType = SearchType.PRODUCT
                    if (binding.recyclerProducts.adapter !== productAdapter) {
                        binding.recyclerProducts.adapter = productAdapter
                    }
                    productAdapter.submitList(productResponse.data.data)
                    return@launch
                }

                // Search vendors
                val vendorResponse = api.searchVendors(query)
                if (vendorResponse.success &&
                    vendorResponse.data?.data?.isNotEmpty() == true) {

                    currentSearchType = SearchType.VENDOR
                    if (binding.recyclerProducts.adapter !== vendorAdapter) {
                        binding.recyclerProducts.adapter = vendorAdapter
                    }
                    vendorAdapter.submitList(vendorResponse.data.data)
                    return@launch
                }

                // No results
                clearResults()
                showEmptyState(true)

            } catch (e: Exception) {
                e.printStackTrace()
                clearResults()
                showEmptyState(true)
                TopBanner.showError(requireActivity(), "Search failed")
            } finally {
                showLoading(false)
            }
        }
    }
    private fun showPriceFilter() {
        val dialogView = layoutInflater.inflate(R.layout.bottom_sheet_price_filter, null)
        val dialog = BottomSheetDialog(requireContext(), R.style.RoundedBottomSheetDialog)
        dialog.setContentView(dialogView)

        dialogView.findViewById<ImageView>(R.id.closeSheet).setOnClickListener {
            dialog.dismiss()
        }

        dialog.show()
    }

    private fun clearResults() {
        if (currentSearchType == SearchType.PRODUCT) {
            productAdapter.submitList(emptyList())
        } else {
            vendorAdapter.submitList(emptyList())
        }
    }


    private fun showLoading(show: Boolean) {
        binding.progressBar.apply {
            indeterminateDrawable.setColorFilter(
                ContextCompat.getColor(requireContext(), R.color.primary_color),
                PorterDuff.Mode.SRC_IN
            )
            val loadingOverlay = binding.loadingOverlay
            loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
