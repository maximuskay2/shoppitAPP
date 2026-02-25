package com.shoppitplus.shoppit.vendor

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.VendorOrderAdapter
import com.shoppitplus.shoppit.databinding.FragmentVendorOrdersBinding
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.launch

class vendorOrders : Fragment() {
    private var _binding: FragmentVendorOrdersBinding? = null
    private val binding get() = _binding!!
    private var currentFilter = "all"
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    private val onOrderClick: (OrderDetail) -> Unit = { order ->
        val bundle = Bundle().apply {
            putString("order_id", order.id)
        }

        findNavController().navigate(
            R.id.action_vendorOrders_to_orderDetails,
            bundle
        )
    }

    private val adapter = VendorOrderAdapter(onOrderClick)

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorOrdersBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        arguments?.let {
            currentFilter = it.getString("status_filter", "all")
        }
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        setupRecyclerView()
        setupChipGroup()
        updateChipSelection()
        fetchOrders()
    }

    private fun setupRecyclerView() {
        binding.rvOrders.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = this@vendorOrders.adapter
            setHasFixedSize(true)
        }
    }

    private fun setupChipGroup() {
        binding.chipGroup.setOnCheckedStateChangeListener { _, checkedIds ->
            currentFilter = when (checkedIds.firstOrNull()) {
                R.id.chipAll -> "all"
                R.id.chipNew -> "new"
                R.id.chipPending -> "pending"
                R.id.chipCompleted -> "completed"
                R.id.chipCancelled -> "cancelled"
                else -> "all"
            }
            fetchOrders()
        }
    }

    private fun updateChipSelection() {
        val chipId = when (currentFilter) {
            "all" -> R.id.chipAll
            "new" -> R.id.chipNew
            "pending" -> R.id.chipPending
            "completed" -> R.id.chipCompleted
            "cancelled" -> R.id.chipCancelled
            else -> R.id.chipAll
        }
        binding.chipGroup.check(chipId)
    }

    private fun fetchOrders() {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = apiClient.getVendorOrders(authToken!!)
                val allOrders = response.data.data

                val filteredOrders = when (currentFilter) {
                    "all" -> allOrders
                    "pending" -> allOrders.filter { it.status.uppercase() == "PENDING" }
                    "completed" -> allOrders.filter { it.status.uppercase() == "COMPLETED" }
                    "cancelled" -> allOrders.filter { it.status.uppercase() == "CANCELLED" }
                    "new" -> allOrders.filter {
                        it.status.uppercase() in listOf("PENDING", "PAID")
                    }
                    else -> allOrders
                }

                adapter.submitList(filteredOrders)
                binding.tvTotal.text = "Total no. ${filteredOrders.size}"
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Failed to load orders", Toast.LENGTH_SHORT).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun showLoading(show: Boolean) {
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
