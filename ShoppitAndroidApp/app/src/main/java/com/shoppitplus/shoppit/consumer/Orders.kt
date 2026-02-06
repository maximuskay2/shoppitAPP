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
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.OrderBottomSheetDialog
import com.shoppitplus.shoppit.utils.Order
import kotlinx.coroutines.launch
import androidx.recyclerview.widget.LinearLayoutManager



class Orders : Fragment() {
   private var _binding: FragmentOrdersBinding? = null
    private val binding get() = _binding!!
private lateinit var adapter: OrdersAdapter

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentOrdersBinding.inflate(inflater, container, false)
        setupRecycler()
        fetchOrders()
        return binding.root

    }
    private fun setupRecycler() {
        adapter = OrdersAdapter {
            OrderBottomSheetDialog(it.id)
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
                val response =
                    RetrofitClient.instance(requireContext()).getOrders()

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

    private fun showOrders(orders: List<Order>) {
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
            val loadingOverlay = binding.loadingOverlay
            loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }
    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}

