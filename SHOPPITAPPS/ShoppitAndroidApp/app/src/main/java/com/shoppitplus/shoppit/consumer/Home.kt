package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.graphics.PorterDuff
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.ImageButton
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.TextView
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.viewpager2.widget.ViewPager2
import com.bumptech.glide.Glide
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.ImageSliderAdapter
import com.shoppitplus.shoppit.adapter.ProductAdapter
import com.shoppitplus.shoppit.adapter.VendorHorizontalAdapter
import com.shoppitplus.shoppit.databinding.FragmentHomeBinding
import com.shoppitplus.shoppit.onboarding.PromoSlide
import com.shoppitplus.shoppit.shared.models.AddToCartRequest
import com.shoppitplus.shoppit.shared.models.ProductDto
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.PromoSliderView
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class Home : Fragment() {

    private var _binding: FragmentHomeBinding? = null
    private val binding get() = _binding!!
    private var hasVendors = false
    private var hasProducts = false
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentHomeBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())

        setupPromoSlider()
        setupLocationClick()
        getUserAccount()

        // Set layout managers
        binding.vendorsRecycler.layoutManager =
            LinearLayoutManager(context, LinearLayoutManager.HORIZONTAL, false)
        binding.productsRecycler.layoutManager =
            LinearLayoutManager(context, LinearLayoutManager.HORIZONTAL, false)

        getNearbyVendors()
        getNewProducts()

        return binding.root
    }

    private fun setupPromoSlider() {
        val promoSlides = listOf(
            PromoSlide(
                "Big Savings This Week!",
                "Up to 25% off top categories — electronics, beauty, and more.",
                R.drawable.ic_sales_bag,
                showButton = true,
                buttonText = "Shop Now"
            ),
            PromoSlide(
                "Support Local. Shop Smart",
                "Find trusted local stores — groceries to gadgets.",
                R.drawable.shopping_bag,
                showButton = true,
                buttonText = "Visit Stores"
            ),
            PromoSlide(
                "Fast Delivery. Always On Time.",
                "Get your orders delivered within hours — safe and reliable.",
                R.drawable.truck,
                showButton = false
            )
        )

        val promoSlider = PromoSliderView(binding.promoViewPager, binding.dotsLayout, promoSlides)
        promoSlider.setupSlider()
    }

    private fun setupLocationClick() {
        binding.locationLayout.setOnClickListener {
            showLocationBottomSheet()
        }
    }

    private fun getNearbyVendors() {
        showLoading(true)
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiClient.getNearbyVendors()
                showLoading(false)

                if (response.success && response.data.data.isNotEmpty()) {
                    hasVendors = true
                    val adapter = VendorHorizontalAdapter(response.data.data) { vendor ->
                        TopBanner.showSuccess(requireActivity(), "Opening ${vendor.name}")
                    }
                    binding.vendorsRecycler.adapter = adapter
                } else {
                    hasVendors = false
                }
                checkForEmptyState()
            } catch (e: Exception) {
                showLoading(false)
                hasVendors = false
                checkForEmptyState()
            }
        }
    }

    private fun getNewProducts() {
        showLoading(true)
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiClient.getNewProducts()
                showLoading(false)

                if (response.success && response.data.isNotEmpty()) {
                    hasProducts = true
                    val adapter = ProductAdapter(
                        products = response.data,
                        contextProvider = { requireContext() },
                        onProductClick = { product -> showProductDetailSheet(product) }
                    )
                    binding.productsRecycler.adapter = adapter
                } else {
                    hasProducts = false
                }
                checkForEmptyState()
            } catch (e: Exception) {
                showLoading(false)
                hasProducts = false
                checkForEmptyState()
            }
        }
    }

    private fun checkForEmptyState() {
        if (!hasVendors && !hasProducts) {
            // Hide sections
            binding.vendorsHeaderLayout.visibility = View.GONE
            binding.vendorsRecycler.visibility = View.GONE
            binding.productsHeaderLayout.visibility = View.GONE
            binding.productsRecycler.visibility = View.GONE

            // Show waitlist screen
            binding.noServiceLayout.visibility = View.VISIBLE

            // Join waitlist button
            binding.btnJoinWaitlist.setOnClickListener {
                joinWaitlist()
            }
        } else {
            // Show normal content
            binding.noServiceLayout.visibility = View.GONE
            binding.vendorsHeaderLayout.visibility = View.VISIBLE
            binding.productsHeaderLayout.visibility = View.VISIBLE
        }
    }

    private fun joinWaitlist() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val response = apiClient.joinWaitlist(authToken!!)
                showLoading(false)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_waitlist_joined))
                    binding.btnJoinWaitlist.isEnabled = false
                    binding.btnJoinWaitlist.text = "Joined ✓"
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
            }
        }
    }

    private fun getUserAccount() {
        val prefs = requireActivity().getSharedPreferences("info", Context.MODE_PRIVATE)

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiClient.getUserAccount(authToken!!)

                if (response.success && response.data != null) {
                    val user = response.data!!

                    Glide.with(requireContext())
                        .load(user.avatar)
                        .placeholder(R.drawable.user_image)
                        .error(R.drawable.user_image)
                        .into(binding.profileIcon)

                    binding.locationText.text = user.state ?: "Select location"

                    prefs.edit().apply {
                        putString("name", user.name)
                        putString("location", user.address)
                        apply()
                    }
                }
            } catch (e: Exception) {
                // Silent fail
            }
        }
    }

    private fun showLocationBottomSheet() {
        val dialogView = layoutInflater.inflate(R.layout.bottom_sheet_location, null)
        val dialog = BottomSheetDialog(requireContext(), R.style.RoundedBottomSheetDialog)
        dialog.setContentView(dialogView)

        val bottomSheet =
            dialog.findViewById<View>(com.google.android.material.R.id.design_bottom_sheet)
        bottomSheet?.setBackgroundResource(R.drawable.rounded_top_sheet_background)

        dialogView.findViewById<ImageView>(R.id.closeSheet)?.setOnClickListener {
            dialog.dismiss()
        }

        dialogView.findViewById<View>(R.id.addNewAddress)?.setOnClickListener {
            dialog.dismiss()
            findNavController().navigate(R.id.action_home_to_editAddress)
        }

        dialog.show()
    }

    private fun showProductDetailSheet(product: ProductDto) {
        val sheet = BottomSheetDialog(requireContext(), R.style.RoundedBottomSheetDialog)
        val view = layoutInflater.inflate(R.layout.bottom_sheet_product_detail, null)
        sheet.setContentView(view)

        val bottomSheet =
            sheet.findViewById<View>(com.google.android.material.R.id.design_bottom_sheet)
        bottomSheet?.setBackgroundResource(R.drawable.rounded_top_sheet_background)

        val imageSlider = view.findViewById<ViewPager2>(R.id.productImageSlider)
        val singleImage = view.findViewById<ImageView>(R.id.singleProductImage)
        val dotsIndicator = view.findViewById<LinearLayout>(R.id.dotsIndicator)

        // Capture avatar list locally to fix smart cast issue
        val avatarList = product.avatar
        if (!avatarList.isNullOrEmpty()) {
            singleImage.visibility = View.GONE
            imageSlider.visibility = View.VISIBLE
            dotsIndicator.visibility = View.INVISIBLE

            val sliderAdapter = ImageSliderAdapter(avatarList.map { it.secureUrl })
            imageSlider.adapter = sliderAdapter

            setupDotsIndicator(dotsIndicator, avatarList.size, imageSlider)

            val handler = Handler(Looper.getMainLooper())
            val autoSlide = object : Runnable {
                override fun run() {
                    val next = (imageSlider.currentItem + 1) % avatarList.size
                    imageSlider.setCurrentItem(next, true)
                    handler.postDelayed(this, 1000)
                }
            }
            handler.postDelayed(autoSlide, 1000)

            sheet.setOnDismissListener { handler.removeCallbacks(autoSlide) }
        } else {
            imageSlider.visibility = View.GONE
            dotsIndicator.visibility = View.GONE
            singleImage.visibility = View.VISIBLE

            Glide.with(requireContext())
                .load(R.drawable.bg_white_circle)
                .into(singleImage)
        }

        view.findViewById<TextView>(R.id.tvName).text = product.name
        view.findViewById<TextView>(R.id.tvDescription).text =
            product.description ?: "Delicious Nigerian dish."
        val displayPrice = product.discountPrice ?: product.price
        view.findViewById<TextView>(R.id.tvPrice).text = "₦$displayPrice"

        var quantity = 1
        val tvQuantity = view.findViewById<TextView>(R.id.tvQuantity)
        val btnCheckout = view.findViewById<Button>(R.id.btnCheckout)

        val updateCheckoutText = {
            val total = quantity * displayPrice
            btnCheckout.text = "Checkout for ₦$total"
        }
        updateCheckoutText()

        view.findViewById<ImageButton>(R.id.btnIncrease).setOnClickListener {
            quantity++
            tvQuantity.text = quantity.toString()
            updateCheckoutText()
            product.id?.let { addToCart(it, quantity) }
        }

        view.findViewById<ImageButton>(R.id.btnDecrease).setOnClickListener {
            if (quantity > 1) {
                quantity--
                tvQuantity.text = quantity.toString()
                updateCheckoutText()
                product.id?.let { addToCart(it, quantity) }
            }
        }

        view.findViewById<ImageView>(R.id.btnClose).setOnClickListener {
            sheet.dismiss()
        }

        btnCheckout.setOnClickListener {
            product.id?.let { addToCart(it, quantity) }
            TopBanner.showSuccess(requireActivity(), "$quantity × ${product.name} added to cart!")
            sheet.dismiss()
        }

        sheet.show()
    }

    private fun setupDotsIndicator(container: LinearLayout, count: Int, viewPager: ViewPager2) {
        val dots = arrayOfNulls<ImageView>(count)
        val layoutParams = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.WRAP_CONTENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        )
        layoutParams.setMargins(8, 0, 8, 0)

        for (i in 0 until count) {
            dots[i] = ImageView(container.context).apply {
                setImageDrawable(ContextCompat.getDrawable(context, R.drawable.dot_inactive))
                this.layoutParams = layoutParams
            }
            container.addView(dots[i])
        }

        dots[0]?.setImageDrawable(
            ContextCompat.getDrawable(
                container.context,
                R.drawable.dot_active
            )
        )

        viewPager.registerOnPageChangeCallback(object : ViewPager2.OnPageChangeCallback() {
            override fun onPageSelected(position: Int) {
                for (i in 0 until count) {
                    dots[i]?.setImageDrawable(
                        ContextCompat.getDrawable(
                            container.context,
                            if (i == position) R.drawable.dot_active else R.drawable.dot_inactive
                        )
                    )
                }
            }
        })
    }

    private fun addToCart(productId: String, qty: Int) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = apiClient.addToCart(authToken!!, AddToCartRequest(productId, qty))

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_cart_updated))
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }
            } catch (e: Exception) {
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
            val loadingOverlay = binding.loadingOverlay
            loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
