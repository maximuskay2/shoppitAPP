package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.graphics.PorterDuff
import android.os.Build
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.CheckoutPackAdapter
import com.shoppitplus.shoppit.databinding.FragmentCheckoutBinding
import com.shoppitplus.shoppit.shared.models.ProcessCartRequest
import com.shoppitplus.shoppit.shared.models.UpdateCartItemRequest
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.LocationHelper
import kotlinx.coroutines.launch

class CheckoutFragment : Fragment() {
    private var _binding: FragmentCheckoutBinding? = null
    private val binding get() = _binding!!

    private var vendorId: String? = null
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    private val locationPermissionLauncher = registerForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { granted ->
        if (granted.values.any { it }) {
            vendorId?.let { processPayment(it) }
        } else {
            showLoading(false)
            TopBanner.showError(requireActivity(), getString(R.string.snack_location_permission))
        }
    }

    private var useWallet = false

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentCheckoutBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        vendorId = arguments?.getString("vendorId")

        if (vendorId == null) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_checkout_error))
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

                val response = apiClient.getVendorCart(authToken!!, vendorId)
                if (response.success && response.data != null) {
                    val cart = response.data!!

                    Glide.with(requireContext()).load(cart.vendor.avatar)
                        .into(binding.vendorAvatar)
                    binding.vendorName.text = cart.vendor.name
                    binding.itemCount.text =
                        "${cart.itemCount} item${if (cart.itemCount != 1) "s" else ""}"

                    binding.packsRecyclerView.layoutManager = LinearLayoutManager(requireContext())
                    binding.packsRecyclerView.adapter =
                        CheckoutPackAdapter(cart.items) { itemId, qty ->
                            updateCartItemQuantity(itemId, qty)
                        }

                    binding.tvSubtotal.text = "₦${cart.subtotal}"
                    binding.tvDeliveryFee.text = "₦${cart.deliveryFee}"
                    binding.tvTotal.text = "₦${cart.vendorTotal}"

                    setupPaymentMethods()
                    setupActions(vendorId)
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                    findNavController().popBackStack()
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
                findNavController().popBackStack()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun setupPaymentMethods() {
        binding.walletPaymentLayout.setOnClickListener {
            if (!binding.rbWallet.isChecked) {
                useWallet = true
                binding.rbWallet.isChecked = true
                binding.rbOnline.isChecked = false
            }
        }

        binding.onlinePaymentLayout.setOnClickListener {
            if (!binding.rbOnline.isChecked) {
                useWallet = false
                binding.rbWallet.isChecked = false
                binding.rbOnline.isChecked = true
            }
        }

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
            findNavController().navigate(R.id.action_checkoutFragment_to_fragment_order_note)
        }

        binding.sendAsGiftLayout.setOnClickListener {
            findNavController().navigate(R.id.action_checkoutFragment_to_fragment_gift_form)
        }

        binding.btnMakePayment.setOnClickListener {
            processPayment(vendorId)
        }
    }

    private fun processPayment(vendorId: String) {
        val cartPrefs = requireActivity().getSharedPreferences("cart_prefs", Context.MODE_PRIVATE)

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val ctx = requireContext()

                if (!LocationHelper.isLocationEnabled(ctx)) {
                    showLoading(false)
                    TopBanner.showError(requireActivity(), getString(R.string.snack_location_services))
                    return@launch
                }
                if (!LocationHelper.hasLocationPermission(ctx)) {
                    showLoading(false)
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.M) {
                        locationPermissionLauncher.launch(
                            arrayOf(
                                android.Manifest.permission.ACCESS_FINE_LOCATION,
                                android.Manifest.permission.ACCESS_COARSE_LOCATION
                            )
                        )
                    } else {
                        TopBanner.showError(requireActivity(), getString(R.string.snack_location_permission))
                    }
                    return@launch
                }
                val location = LocationHelper.getLastLocation(ctx)
                if (location == null) {
                    showLoading(false)
                    TopBanner.showError(requireActivity(), getString(R.string.snack_location_error))
                    return@launch
                }

                val zoneResponse = apiClient.checkDeliveryZone(authToken!!, location.latitude, location.longitude)
                if (!zoneResponse.success || zoneResponse.data?.inZone != true) {
                    showLoading(false)
                    TopBanner.showError(requireActivity(), getString(R.string.snack_outside_delivery_zone))
                    return@launch
                }

                val request = ProcessCartRequest(
                    vendorId = vendorId,
                    orderNotes = cartPrefs.getString("order_note", ""),
                    walletUsage = if (useWallet) 1 else 0,
                    isGift = if (cartPrefs.contains("gift_recipient_name")) 1 else 0,
                    receiverName = cartPrefs.getString("gift_recipient_name", null),
                    receiverEmail = cartPrefs.getString("gift_recipient_email", null),
                    receiverPhone = cartPrefs.getString("gift_recipient_phone", null),
                    receiverDeliveryAddress = cartPrefs.getString("location", null),
                    deliveryLatitude = location.latitude,
                    deliveryLongitude = location.longitude
                )

                val response = apiClient.processCart(authToken!!, request)

                showLoading(false)

                if (response.success && response.data != null) {
                    if (useWallet) {
                        TopBanner.showSuccess(requireActivity(), getString(R.string.snack_order_placed))
                        findNavController().popBackStack()
                    } else {
                        val bundle = Bundle().apply {
                            putString("url", response.data!!.authorizationUrl)
                        }
                        findNavController().navigate(R.id.action_checkoutFragment_to_fragment_paystack_webview, bundle)
                    }
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
            }
        }
    }

    private fun updateCartItemQuantity(itemId: String, qty: Int) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = if (qty == 0) {
                    apiClient.deleteCartItem(authToken!!, itemId)
                } else {
                    apiClient.updateCartItem(authToken!!, itemId, UpdateCartItemRequest(qty))
                }
                showLoading(false)
                if (response.success) {
                        TopBanner.showSuccess(requireActivity(), if (qty == 0) getString(R.string.snack_item_removed) else getString(R.string.snack_quantity_updated))
                    loadCheckoutData(vendorId!!)
                }
            } catch (e: Exception) {
                showLoading(false)
                    TopBanner.showError(requireActivity(), getString(R.string.snack_update_failed))
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

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
