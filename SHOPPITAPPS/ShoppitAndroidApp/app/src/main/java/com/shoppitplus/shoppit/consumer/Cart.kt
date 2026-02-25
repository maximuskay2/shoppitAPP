package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.CartVendorAdapter
import com.shoppitplus.shoppit.databinding.FragmentCartBinding
import com.shoppitplus.shoppit.shared.models.CartData
import com.shoppitplus.shoppit.shared.models.CartVendor
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class Cart : Fragment() {

    private var _binding: FragmentCartBinding? = null
    private val binding get() = _binding!!

    private var deliveryAddress: String = "Select delivery address"
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentCartBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.cartRecyclerView.layoutManager = LinearLayoutManager(requireContext())

        binding.deleteCartBtn.setOnClickListener {
            clearCart()
        }

        val prefs = requireActivity().getSharedPreferences("info", Context.MODE_PRIVATE)
        deliveryAddress = prefs.getString("location", "Select delivery address") ?: "Select delivery address"

        loadCart()
    }

    private fun loadCart() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)

                val response = apiClient.getCart(authToken!!)

                showLoading(false)

                val cartData = response.data
                if (response.success && cartData != null && cartData.vendors.isNotEmpty()) {
                    showCartContent(cartData.vendors, cartData)
                } else {
                    showEmptyState()
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_load_failed))
                showEmptyState()
            }
        }
    }


    private fun showCartContent(vendors: List<CartVendor>, cartData: CartData) {
        binding.emptyState.visibility = View.GONE

        val adapter = CartVendorAdapter(
            vendors = vendors,
            deliveryAddress = deliveryAddress,
            onVendorCheckout = { vendorId ->
                val bundle = Bundle().apply {
                    putString("vendorId", vendorId)
                }
                findNavController().navigate(R.id.action_cart_to_checkoutFragment, bundle)
            },
            onClearVendor = { cartVendor ->
                clearVendorCart(cartVendor.vendor.id)
            }
        )
        binding.cartRecyclerView.adapter = adapter
    }

    private fun clearCart() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = apiClient.clearCart(authToken!!)
                showLoading(false)

                if (response.success) {
                    showLoading(false)
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_cart_cleared))
                    showEmptyState()
                } else {
                    showLoading(false)
                    TopBanner.showError(requireActivity(), response.message)
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
            }
        }
    }

    private fun showLoading(show: Boolean) {
        binding.progressBar.apply {
            indeterminateDrawable.setColorFilter(
                ContextCompat.getColor(requireContext(), R.color.primary_color),
                PorterDuff.Mode.SRC_IN
            )
            binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }

    private fun showEmptyState() {
        binding.emptyState.visibility = View.VISIBLE
    }

    private fun clearVendorCart(vendorId: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = apiClient.clearVendorCart(authToken!!, vendorId)
                showLoading(false)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_vendor_cart_cleared))
                    loadCart()
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
