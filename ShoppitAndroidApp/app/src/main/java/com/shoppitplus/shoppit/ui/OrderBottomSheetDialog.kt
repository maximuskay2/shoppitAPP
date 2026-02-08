package com.shoppitplus.shoppit.ui

import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.google.android.material.bottomsheet.BottomSheetDialogFragment
import com.shoppitplus.shoppit.adapter.OrderItemsAdapter
import com.shoppitplus.shoppit.databinding.BottomSheetOrderSummaryBinding
import com.shoppitplus.shoppit.consumer.OrderTrackingActivity
import com.shoppitplus.shoppit.consumer.RateDriverActivity
import com.shoppitplus.shoppit.models.RetrofitClient
import kotlinx.coroutines.launch

class OrderBottomSheetDialog(private val orderId: String) : BottomSheetDialogFragment() {

    private var _binding: BottomSheetOrderSummaryBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?): View {
        _binding = BottomSheetOrderSummaryBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.btnClose.setOnClickListener { dismiss() }

        loadOrder()
    }

    private fun loadOrder() {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getOrderById(orderId)
                if (response.isSuccessful && response.body()?.data != null) {
                    val order = response.body()!!.data

                    // Order details
                    binding.tvOrderId.text = order.tracking_id
                    binding.tvOrderDate.text = formatDate(order.created_at)
                    binding.tvDeliveryFee.text = "₦${order.delivery_fee}"
                    binding.tvTotalAmount.text = "₦${String.format("%,d", order.net_total_amount)}"
                    binding.tvStatus.text = order.status.uppercase()

                    // Items
                    binding.itemsRecycler.layoutManager = LinearLayoutManager(requireContext())
                    binding.itemsRecycler.adapter = OrderItemsAdapter(order.line_items)

                    // Action button
                    when (order.status.uppercase()) {
                        "DELIVERED" -> {
                            binding.actionButton.text = "Rate Driver"
                            binding.actionButton.visibility = View.VISIBLE
                            binding.actionButton.setOnClickListener {
                                val intent = Intent(requireContext(), RateDriverActivity::class.java)
                                intent.putExtra("order_id", order.id)
                                startActivity(intent)
                                dismiss()
                            }
                        }
                        "PAID", "PENDING", "DISPATCHED", "OUT_FOR_DELIVERY", "PICKED_UP" -> {
                            binding.actionButton.text = "Track Order"
                            binding.actionButton.visibility = View.VISIBLE
                            binding.actionButton.setOnClickListener {
                                val intent = Intent(requireContext(), OrderTrackingActivity::class.java)
                                intent.putExtra("order_id", order.id)
                                startActivity(intent)
                                dismiss()
                            }
                        }
                        else -> binding.actionButton.visibility = View.GONE
                    }
                }
            } catch (e: Exception) {
                e.printStackTrace()
            }
        }
    }

    private fun formatDate(dateString: String): String {
        // Convert "2025-12-27T04:48:39.000000Z" → "Dec 27, 2025"
        return try {
            val parts = dateString.split("T")[0].split("-")
            val month = when (parts[1]) {
                "01" -> "Jan"; "02" -> "Feb"; "03" -> "Mar"; "04" -> "Apr"
                "05" -> "May"; "06" -> "Jun"; "07" -> "Jul"; "08" -> "Aug"
                "09" -> "Sep"; "10" -> "Oct"; "11" -> "Nov"; "12" -> "Dec"
                else -> parts[1]
            }
            "$month ${parts[2]}, ${parts[0]}"
        } catch (e: Exception) {
            dateString
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}