package com.shoppitplus.shoppit.consumer

import android.graphics.PorterDuff
import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.OrdersAdapter
import com.shoppitplus.shoppit.databinding.FragmentOrdersBinding
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.OrderBottomSheetDialog
import kotlinx.coroutines.launch
import androidx.recyclerview.widget.LinearLayoutManager

class Orders : Fragment() {
    private var _binding: FragmentOrdersBinding? = null
    private val binding get() = _binding!!
    private lateinit var adapter: OrdersAdapter
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentOrdersBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        setupRecycler()
        fetchOrders()
        return binding.root
    }

    private fun setupRecycler() {
        adapter = OrdersAdapter { order ->
            OrderBottomSheetDialog(order.id)
                .show(parentFragmentManager, "order_sheet")
        }

        binding.ordersRecyclerView.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = this@Orders.adapter
            setHasFixedSize(true)
        }
    }

    private fun fetchOrders() {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = apiClient.getOrders(authToken!!)

                val orders = response.data.data

                if (orders.isEmpty()) {
                    showEmpty()
                } else {
                    showOrders(orders)
                }
            } catch (e: Exception) {
                showEmpty()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun showEmpty() {
        binding.emptyOrders.root.visibility = View.VISIBLE
        binding.ordersRecyclerView.visibility = View.GONE
    }

    private fun showOrders(orders: List<OrderDetail>) {
        binding.emptyOrders.root.visibility = View.GONE
        binding.ordersRecyclerView.visibility = View.VISIBLE
        adapter.submitList(orders)
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
