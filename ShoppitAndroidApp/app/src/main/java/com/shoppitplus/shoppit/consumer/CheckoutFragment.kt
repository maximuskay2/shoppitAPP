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
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.CheckoutPackAdapter
import com.shoppitplus.shoppit.databinding.FragmentCheckoutBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.ProcessCartRequest
import com.shoppitplus.shoppit.utils.UpdateCartItemRequest
import kotlinx.coroutines.launch

class CheckoutFragment : Fragment() {
    private var _binding: FragmentCheckoutBinding? = null
    private val binding get() = _binding!!

    private var vendorId: String? = null

    private var useWallet = false
    private var isGift = false
    private var currentOrderNotes = ""

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentCheckoutBinding.inflate(inflater, container, false)
        return binding.root

    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        // Receive vendorId from bundle
        vendorId = arguments?.getString("vendorId")

        if (vendorId == null) {
            TopBanner.showError(requireActivity(), "Error loading checkout")
            findNavController().popBackStack()
            return
        }

        binding.backButton.setOnClickListener {
            findNavController().popBackStack()
        }

        loadCheckoutData(vendorId!!)
    }


    private fun loadCheckoutData(vendorId: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)

                val response = RetrofitClient.instance(requireContext()).getVendorCart(vendorId)
                if (response.success && response.data != null) {
                    val cart = response.data

                    // Vendor
                    Glide.with(requireContext()).load(cart.vendor.avatar)
                        .into(binding.vendorAvatar)
                    binding.vendorName.text = cart.vendor.name
                    binding.itemCount.text =
                        "${cart.item_count} item${if (cart.item_count != 1) "s" else ""}"

                    // Packs
                    binding.packsRecyclerView.layoutManager = LinearLayoutManager(requireContext())
                    binding.packsRecyclerView.adapter =
                        CheckoutPackAdapter(cart.items) { itemId, qty ->
                            updateCartItemQuantity(itemId, qty)
                        }

                    // Summary
                    binding.tvSubtotal.text = "₦${cart.subtotal}"
                    binding.tvDeliveryFee.text = "₦${cart.delivery_fee}"
                    binding.tvTotal.text = "₦${cart.vendor_total}"

                    setupPaymentMethods()
                    setupActions(vendorId)
                } else {
                    TopBanner.showError(requireActivity(), "Failed to load checkout")
                    findNavController().popBackStack()
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Network error")
                findNavController().popBackStack()
            } finally {
                showLoading(false)  // ← Always hide loading
            }
        }
    }

    private fun setupPaymentMethods() {
        // Wallet row click
        binding.walletPaymentLayout.setOnClickListener {
            if (!binding.rbWallet.isChecked) {  // Only if not already selected
                useWallet = true
                binding.rbWallet.isChecked = true
                binding.rbOnline.isChecked = false
            }
        }

        // Online row click
        binding.onlinePaymentLayout.setOnClickListener {
            if (!binding.rbOnline.isChecked) {  // Only if not already selected
                useWallet = false
                binding.rbWallet.isChecked = false
                binding.rbOnline.isChecked = true
            }
        }

        // Optional: Also listen to actual RadioButton clicks (for direct tap on circle)
        binding.rbWallet.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) {
                useWallet = true
                binding.rbOnline.isChecked = false
            }
        }

        binding.rbOnline.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) {
                useWallet = false
                binding.rbWallet.isChecked = false
            }
        }
    }

    private fun setupActions(vendorId: String) {
        binding.leaveMessageLayout.setOnClickListener {
            findNavController().navigate(
                R.id.action_checkoutFragment_to_fragment_order_note
            )
        }

        binding.sendAsGiftLayout.setOnClickListener {
            findNavController().navigate(
                R.id.action_checkoutFragment_to_fragment_gift_form
            )
            isGift = true
        }

        binding.btnMakePayment.setOnClickListener {
            processPayment(vendorId)
        }
    }

    private fun processPayment(vendorId: String) {
        val cartPrefs = requireActivity().getSharedPreferences("cart_prefs", Context.MODE_PRIVATE)
        val infoPrefs = requireActivity().getSharedPreferences("info", Context.MODE_PRIVATE)

        val finalNotes = cartPrefs.getString("order_note", "") ?: ""
        val isGift = cartPrefs.contains("gift_recipient_name")
        val walletUsage = if (useWallet) 1 else 0

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val request = ProcessCartRequest(
                    vendor_id = vendorId,
                    order_notes = finalNotes,
                    wallet_usage = walletUsage,
                    is_gift = if (isGift) 1 else 0,
                    receiver_name = cartPrefs.getString("gift_recipient_name", null),
                    receiver_email = cartPrefs.getString("gift_recipient_email", null),
                    receiver_phone = cartPrefs.getString("gift_recipient_phone", null),
                    receiver_delivery_address = cartPrefs.getString("location", null) // or use gift-specific if needed
                )
                val response = RetrofitClient.instance(requireContext()).processCart(request)

                showLoading(false)

                if (response.success && response.data != null) {
                    if (useWallet) {
                        TopBanner.showSuccess(requireActivity(), "Order placed with wallet!")
                        findNavController().popBackStack()
                    } else {
                        // Inside CheckoutFragment, when navigating to Paystack WebView
                        val bundle = Bundle().apply {
                            putString("url", response.data.authorization_url)
                        }
                        findNavController().navigate(
                            R.id.action_checkoutFragment_to_fragment_paystack_webview,
                            bundle
                        )

                    }
                } else {
                    TopBanner.showError(requireActivity(), response.message ?: "Payment failed")
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Network error")
            }
        }
    }

    private fun updateCartItemQuantity(itemId: String, qty: Int) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = if (qty == 0) {
                    RetrofitClient.instance(requireContext()).deleteCartItem(itemId)
                } else {
                    RetrofitClient.instance(requireContext()).updateCartItem(
                        itemId,
                        UpdateCartItemRequest(qty)
                    )
                }
                showLoading(false)
                if (response.success) {
                    TopBanner.showSuccess(
                        requireActivity(),
                        if (qty == 0) "Item removed" else "Quantity updated"
                    )
                    loadCheckoutData(vendorId!!) // Refresh current screen
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Update failed")
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

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}