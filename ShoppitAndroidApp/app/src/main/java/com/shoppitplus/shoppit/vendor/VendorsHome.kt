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
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.StatsResponse
import com.shoppitplus.shoppit.utils.UnreadCountResponse
import com.shoppitplus.shoppit.utils.VendorAnalyticsResponse
import com.shoppitplus.shoppit.utils.VendorResponse
import kotlinx.coroutines.launch
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.util.Calendar
import java.util.TimeZone

class VendorsHome : Fragment() {

    private var _binding: FragmentVendorsHomeBinding? = null
    private val binding get() = _binding!!

    // Track how many API calls are in progress
    private var pendingApiCalls = 0

    // Selected month and year for stats
    private var selectedYear: Int = Calendar.getInstance().get(Calendar.YEAR)
    private var selectedMonth: Int = Calendar.getInstance().get(Calendar.MONTH) + 1 // 1-12

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorsHomeBinding.inflate(inflater, container, false)
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

        // Start loading all data
        loadAllData()
    }

    private fun loadAllData() {
        // Reset counter
        pendingApiCalls = 0

        // Start all API calls
        startApiCall { fetchUnreadNotificationCount() }
        startApiCall { fetchVendorDetails() }
        startApiCall { loadWalletBalance() }
        startApiCall { fetchOrderStatistics(selectedYear, selectedMonth) }
        startApiCall { fetchAnalytics() }

        // Show loading if any call is pending
        if (pendingApiCalls > 0) {
            showLoading(true)
        }
    }

    private fun refreshOrderStatistics() {
        pendingApiCalls = 0
        startApiCall { loadWalletBalance() }
        startApiCall { fetchOrderStatistics(selectedYear, selectedMonth) }

        if (pendingApiCalls > 0) {
            showLoading(true)
        }
    }

    private fun startApiCall(block: () -> Unit) {
        pendingApiCalls++
        block()
    }

    private fun apiCallCompleted() {
        pendingApiCalls--
        if (pendingApiCalls <= 0) {
            pendingApiCalls = 0
            showLoading(false)
            binding.swipeRefreshVendorHome.isRefreshing = false
        }
    }

    private fun fetchUnreadNotificationCount() {
        RetrofitClient.instance(requireContext())
            .getUnreadNotificationCount()
            .enqueue(object : Callback<UnreadCountResponse> {
                override fun onResponse(
                    call: Call<UnreadCountResponse>,
                    response: Response<UnreadCountResponse>
                ) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        val unreadCount = response.body()?.data?.unread ?: 0
                        binding.tvNotificationBadge.apply {
                            text = unreadCount.toString()
                            visibility = if (unreadCount > 0) View.VISIBLE else View.GONE
                        }
                    } else {
                        binding.tvNotificationBadge.visibility = View.GONE
                    }
                    apiCallCompleted()
                }

                override fun onFailure(call: Call<UnreadCountResponse>, t: Throwable) {
                    binding.tvNotificationBadge.visibility = View.GONE
                    apiCallCompleted()
                }
            })
    }

    private fun fetchVendorDetails() {
        RetrofitClient.instance(requireContext()).getVendorDetails()
            .enqueue(object : Callback<VendorResponse> {
                override fun onResponse(
                    call: Call<VendorResponse>,
                    response: Response<VendorResponse>
                ) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        val name =
                            response.body()?.data?.name?.split(" ")?.firstOrNull() ?: "Vendor"
                        binding.tvWelcome.text = "Welcome back, $name"
                    } else {
                        handleError("Failed to load profile")
                    }
                    apiCallCompleted()
                }

                override fun onFailure(call: Call<VendorResponse>, t: Throwable) {
                    handleError("Network error loading profile")
                    apiCallCompleted()
                }
            })
    }

    private fun loadWalletBalance() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val balanceResponse = RetrofitClient.instance(requireContext()).getWalletBalance()
                if (balanceResponse.success) {
                    val balance = balanceResponse.data.balance.toDouble()
                    binding.tvTotalBalance.text = "₦${String.format("%,.0f", balance)}"
                } else {
                    binding.tvTotalBalance.text = "₦0"
                }
            } catch (e: Exception) {
                binding.tvTotalBalance.text = "₦-"
                Log.e("VendorsHome", "Wallet load error", e)
            } finally {
                apiCallCompleted()
            }
        }
    }

    private fun fetchOrderStatistics(year: Int, month: Int) {
        RetrofitClient.instance(requireContext())
            .getOrderStatistics(year, month)
            .enqueue(object : Callback<StatsResponse> {
                override fun onResponse(call: Call<StatsResponse>, response: Response<StatsResponse>) {
                    if (response.isSuccessful && response.body()?.success == true) {
                        val data = response.body()!!.data
                        val orders = data.orders
                        val revenue = data.revenue

                        val cleanTotalRevenue = revenue.total_revenue.toSafeDouble()
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
                    apiCallCompleted()
                }

                override fun onFailure(call: Call<StatsResponse>, t: Throwable) {
                    binding.tvMonthlyEarning.text = "₦-"
                    handleError("Failed to load earnings")
                    apiCallCompleted()
                }
            })
    }

    private fun String.toSafeDouble(): Double {
        return this.replace(",", "").toDoubleOrNull() ?: 0.0
    }

    private fun fetchAnalytics() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.getVendorAnalyticsSummary()
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()?.data
                    val topProducts = data?.topProducts.orEmpty()
                    val salesTrends = data?.salesTrends.orEmpty()
                    view?.post {
                        populateTopProducts(topProducts)
                        populateSalesTrends(salesTrends)
                    }
                }
            } catch (e: Exception) {
                Log.e("VendorsHome", "Analytics load error", e)
            } finally {
                apiCallCompleted()
            }
        }
    }

    private fun populateTopProducts(items: List<com.shoppitplus.shoppit.utils.TopProductItem>) {
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

    private fun populateSalesTrends(items: List<com.shoppitplus.shoppit.utils.SalesTrendItem>) {
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
        val monthName = when (selectedMonth) {
            1 -> "January"
            2 -> "February"
            3 -> "March"
            4 -> "April"
            5 -> "May"
            6 -> "June"
            7 -> "July"
            8 -> "August"
            9 -> "September"
            10 -> "October"
            11 -> "November"
            12 -> "December"
            else -> "Unknown"
        }
        binding.tvMonthSelector.text = "Earning in $monthName"
    }

    private fun showLoading(show: Boolean) {
        with(binding) {
            if (show) {
                // Tint progress bar with primary color
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
        view?.post {
            Toast.makeText(requireContext(), errorMessage, Toast.LENGTH_SHORT).show()
        }
        Log.e("VendorsHome", errorMessage)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}