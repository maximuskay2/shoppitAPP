package com.shoppitplus.shoppit.vendor

import android.graphics.PorterDuff
import android.os.Bundle
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.google.android.material.datepicker.CalendarConstraints
import com.google.android.material.datepicker.DateValidatorPointBackward
import com.google.android.material.datepicker.MaterialDatePicker
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorsHomeBinding
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.shared.models.SalesTrendItem
import com.shoppitplus.shoppit.shared.models.TopProductItem
import kotlinx.coroutines.launch
import java.util.Calendar
import java.util.TimeZone

class VendorsHome : Fragment() {

    private var _binding: FragmentVendorsHomeBinding? = null
    private val binding get() = _binding!!

    private var pendingApiCalls = 0
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    private var selectedYear: Int = Calendar.getInstance().get(Calendar.YEAR)
    private var selectedMonth: Int = Calendar.getInstance().get(Calendar.MONTH) + 1

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorsHomeBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.notificationContainer.setOnClickListener {
            findNavController().navigate(R.id.action_vendorsHome_to_notification)
        }

        setupMonthPicker()
        updateMonthDisplay()

        binding.swipeRefreshVendorHome.setOnRefreshListener {
            loadAllData()
        }

        loadAllData()
    }

    private fun loadAllData() {
        pendingApiCalls = 0
        showLoading(true)

        startApiCall { fetchUnreadNotificationCount() }
        startApiCall { fetchVendorDetails() }
        startApiCall { loadWalletBalance() }
        startApiCall { fetchOrderStatistics(selectedYear, selectedMonth) }
        startApiCall { fetchAnalytics() }
    }

    private fun refreshOrderStatistics() {
        pendingApiCalls = 0
        showLoading(true)
        startApiCall { loadWalletBalance() }
        startApiCall { fetchOrderStatistics(selectedYear, selectedMonth) }
    }

    private fun startApiCall(block: suspend () -> Unit) {
        pendingApiCalls++
        lifecycleScope.launch {
            try {
                block()
            } catch (e: Exception) {
                Log.e("VendorsHome", "API call error", e)
            } finally {
                apiCallCompleted()
            }
        }
    }

    private fun apiCallCompleted() {
        pendingApiCalls--
        if (pendingApiCalls <= 0) {
            pendingApiCalls = 0
            showLoading(false)
            binding.swipeRefreshVendorHome.isRefreshing = false
        }
    }

    private suspend fun fetchUnreadNotificationCount() {
        val response = apiClient.getUnreadNotificationCount(authToken!!)
        if (response.success && response.data != null) {
            val unreadCount = response.data!!.unread
            binding.tvNotificationBadge.apply {
                text = unreadCount.toString()
                visibility = if (unreadCount > 0) View.VISIBLE else View.GONE
            }
        } else {
            binding.tvNotificationBadge.visibility = View.GONE
        }
    }

    private suspend fun fetchVendorDetails() {
        val response = apiClient.getVendorDetails(authToken!!)
        if (response.success && response.data != null) {
            val name = response.data!!.name.split(" ").firstOrNull() ?: "Vendor"
            binding.tvWelcome.text = "Welcome back, $name"
        } else {
            handleError("Failed to load profile")
        }
    }

    private suspend fun loadWalletBalance() {
        val balanceResponse = apiClient.getWalletBalance(authToken!!)
        if (balanceResponse.success) {
            val balance = balanceResponse.data.balance
            binding.tvTotalBalance.text = "₦${String.format("%,.0f", balance)}"
        } else {
            binding.tvTotalBalance.text = "₦0"
        }
    }

    private suspend fun fetchOrderStatistics(year: Int, month: Int) {
        val response = apiClient.getOrderStatistics(authToken!!, year, month)
        if (response.success) {
            val data = response.data
            val orders = data.orders
            val revenue = data.revenue

            val cleanTotalRevenue = revenue.totalRevenue.toSafeDouble()
            binding.tvMonthlyEarning.text = "₦${String.format("%,.0f", cleanTotalRevenue)}"

            binding.tvNewOrders.text = orders.total.toString().padStart(2, '0')
            binding.tvPendingOrders.text = orders.pending.toString().padStart(2, '0')
            binding.tvCompletedOrders.text = orders.completed.toString().padStart(2, '0')
            binding.tvCancelOrders.text = orders.cancelled.toString().padStart(2, '0')

            binding.tvAllOrders.text = "All order (${orders.total}) >"
        } else {
            binding.tvMonthlyEarning.text = "₦0"
            handleError("No earnings data available")
        }
    }

    private fun String.toSafeDouble(): Double {
        return this.replace(",", "").toDoubleOrNull() ?: 0.0
    }

    private suspend fun fetchAnalytics() {
        val response = apiClient.getVendorAnalyticsSummary(authToken!!)
        if (response.success && response.data != null) {
            val data = response.data!!
            val topProducts = data.topProducts.orEmpty()
            val salesTrends = data.salesTrends.orEmpty()
            populateTopProducts(topProducts)
            populateSalesTrends(salesTrends)
        }
    }

    private fun populateTopProducts(items: List<TopProductItem>) {
        val container = binding.analyticsTopProductsContainer
        container.removeAllViews()
        if (items.isEmpty()) {
            val tv = android.widget.TextView(requireContext()).apply {
                text = "No sales yet"
                setTextColor(ContextCompat.getColor(requireContext(), android.R.color.darker_gray))
                textSize = 12f
            }
            container.addView(tv)
            return
        }
        for (item in items) {
            val tv = android.widget.TextView(requireContext()).apply {
                text = "${item.name} (${item.salesCount} sold)"
                setTextColor(ContextCompat.getColor(requireContext(), R.color.black))
                textSize = 13f
                setPadding(0, 4, 0, 4)
            }
            container.addView(tv)
        }
    }

    private fun populateSalesTrends(items: List<SalesTrendItem>) {
        val container = binding.analyticsTrendsContainer
        container.removeAllViews()
        if (items.isEmpty()) {
            val tv = android.widget.TextView(requireContext()).apply {
                text = "No data yet"
                setTextColor(ContextCompat.getColor(requireContext(), android.R.color.darker_gray))
                textSize = 12f
            }
            container.addView(tv)
            return
        }
        for (item in items) {
            val tv = android.widget.TextView(requireContext()).apply {
                text = "${item.month}: ${item.orders} orders, ₦${String.format("%,.0f", item.revenue)}"
                setTextColor(ContextCompat.getColor(requireContext(), R.color.black))
                textSize = 13f
                setPadding(0, 4, 0, 4)
            }
            container.addView(tv)
        }
    }

    private fun setupMonthPicker() {
        binding.tvMonthSelector.setOnClickListener {
            showMonthYearPicker()
        }
    }

    private fun showMonthYearPicker() {
        val constraints = CalendarConstraints.Builder()
            .setValidator(DateValidatorPointBackward.now())
            .build()

        val picker = MaterialDatePicker.Builder.datePicker()
            .setCalendarConstraints(constraints)
            .setSelection(MaterialDatePicker.todayInUtcMilliseconds())
            .setTitleText("Select Month")
            .build()

        picker.show(childFragmentManager, "MONTH_PICKER")

        picker.addOnPositiveButtonClickListener { selection ->
            val calendar = Calendar.getInstance(TimeZone.getTimeZone("UTC"))
            calendar.timeInMillis = selection

            selectedYear = calendar.get(Calendar.YEAR)
            selectedMonth = calendar.get(Calendar.MONTH) + 1

            updateMonthDisplay()
            refreshOrderStatistics()
        }
    }

    private fun updateMonthDisplay() {
        val monthNames = arrayOf("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December")
        val monthName = if (selectedMonth in 1..12) monthNames[selectedMonth - 1] else "Unknown"
        binding.tvMonthSelector.text = "Earning in $monthName"
    }

    private fun showLoading(show: Boolean) {
        with(binding) {
            if (show) {
                progressBar.indeterminateDrawable.setColorFilter(
                    ContextCompat.getColor(requireContext(), R.color.primary_color),
                    PorterDuff.Mode.SRC_IN
                )
                loadingOverlay.visibility = View.VISIBLE
                progressBar.visibility = View.VISIBLE
            } else {
                loadingOverlay.visibility = View.GONE
                progressBar.visibility = View.GONE
            }
        }
    }

    private fun handleError(errorMessage: String) {
        Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_SHORT).show()
        Log.e("VendorsHome", errorMessage)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
