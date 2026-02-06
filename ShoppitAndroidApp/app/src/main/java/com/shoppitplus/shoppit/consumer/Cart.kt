package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.content.res.Resources
import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.bumptech.glide.Glide
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.CartVendorAdapter
import com.shoppitplus.shoppit.databinding.FragmentCartBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.CartData
import com.shoppitplus.shoppit.utils.CartVendor
import kotlinx.coroutines.launch
import android.widget.TextView
import android.widget.LinearLayout
import androidx.appcompat.widget.AppCompatButton
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.adapter.CheckoutPackAdapter
import com.shoppitplus.shoppit.utils.ProcessCartRequest
import com.shoppitplus.shoppit.utils.UpdateCartItemRequest
import android.widget.RadioButton

class Cart : Fragment() {

    private var _binding: FragmentCartBinding? = null
    private val binding get() = _binding!!

    private var deliveryAddress: String = "Select delivery address"

    private var currentOrderNotes = ""
    private var isGift = false
    private var useWallet = false

    private var currentCheckoutSheet: BottomSheetDialog? = null
    private var currentVendorId: String = ""


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentCartBinding.inflate(inflater, container, false)



        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.cartRecyclerView.layoutManager = LinearLayoutManager(requireContext())

        // Delete entire cart button
        binding.deleteCartBtn.setOnClickListener {
            clearCart()
        }

        // Load address
        val prefs = requireActivity().getSharedPreferences("info", Context.MODE_PRIVATE)
        deliveryAddress = prefs.getString("location", "Select delivery address") ?: "Select delivery address"

        loadCart()
    }

    private fun loadCart() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)

                val response = RetrofitClient.instance(requireContext()).getCart()

                showLoading(false)

                if (response.success && response.data != null && response.data.vendors.isNotEmpty()) {
                    showCartContent(response.data.vendors, response.data)
                } else {
                    showEmptyState()
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Failed to load cart")
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
                val response = RetrofitClient.instance(requireContext()).clearCart()
                showLoading(false)

                if (response.success) {
                    showLoading(false)
                    TopBanner.showSuccess(requireActivity(), "Cart cleared successfully")
                    showEmptyState()
                } else {
                    showLoading(false)
                    TopBanner.showError(
                        requireActivity(),
                        response.message ?: "Failed to clear cart"
                    )
                }
            } catch (e: Exception) {
                showLoading(false)


                TopBanner.showError(requireActivity(), "Network error")
            }
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

    private fun showEmptyState() {
        binding.emptyState.visibility = View.VISIBLE
        // binding.bottomBar.visibility = View.GONE
    }

    private fun clearVendorCart(vendorId: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = RetrofitClient.instance(requireContext()).clearVendorCart(vendorId)
                showLoading(false)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), "Items from this vendor cleared")
                    loadCart() // Refresh the entire cart
                } else {
                    TopBanner.showError(requireActivity(), response.message ?: "Failed to clear items")
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Network error")
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}