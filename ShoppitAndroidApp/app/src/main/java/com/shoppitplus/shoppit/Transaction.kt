package com.shoppitplus.shoppit

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.util.Log
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.databinding.FragmentTransactionBinding
import com.shoppitplus.shoppit.databinding.ItemVendorPayoutBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.VendorPayoutItem
import com.shoppitplus.shoppit.utils.VendorPayoutRequest
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Locale

class Transaction : Fragment() {
    private var _binding: FragmentTransactionBinding? = null
    private val binding get() = _binding!!

    private val payouts = mutableListOf<VendorPayoutItem>()
    private lateinit var adapter: PayoutAdapter
    private val tag = "VendorTransaction"

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentTransactionBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupRecycler()
        binding.btnWithdraw.setOnClickListener { requestPayout() }
        loadBalance()
        loadPayouts()
    }

    private fun setupRecycler() {
        adapter = PayoutAdapter(payouts)
        binding.payoutsRecycler.layoutManager = LinearLayoutManager(requireContext())
        binding.payoutsRecycler.adapter = adapter
    }

    private fun loadBalance() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getWalletBalance()
                if (response.success) {
                    binding.tvBalance.text = "₦${String.format("%,.0f", response.data.balance)}"
                }
            } catch (_: Exception) {
                Log.w(tag, "Balance load failed")
            }
        }
    }

    private fun loadPayouts() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getVendorPayouts()
                if (response.isSuccessful && response.body()?.success == true) {
                    val items = response.body()?.data?.data ?: emptyList()
                    payouts.clear()
                    payouts.addAll(items)
                    adapter.notifyDataSetChanged()
                    Log.d(tag, "Loaded ${items.size} payouts")
                }
            } catch (_: Exception) {
                Log.w(tag, "Payouts load failed")
            }
        }
    }

    private fun requestPayout() {
        val amountText = binding.inputWithdraw.text.toString().trim()
        val amount = amountText.toDoubleOrNull()
        if (amount == null || amount <= 0) {
            Toast.makeText(requireContext(), "Enter a valid amount", Toast.LENGTH_SHORT).show()
            return
        }

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                Log.d(tag, "Requesting payout: $amount")
                val response = RetrofitClient.instance(requireContext())
                    .requestVendorPayout(VendorPayoutRequest(amount))
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Payout request sent", Toast.LENGTH_SHORT).show()
                    binding.inputWithdraw.setText("")
                    loadBalance()
                    loadPayouts()
                } else {
                    Log.w(tag, "Payout request failed: ${response.body()?.message}")
                    Toast.makeText(
                        requireContext(),
                        response.body()?.message ?: "Request failed",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            } catch (_: Exception) {
                Log.w(tag, "Payout request failed: network error")
                Toast.makeText(requireContext(), "Network error", Toast.LENGTH_SHORT).show()
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    private class PayoutAdapter(private val items: List<VendorPayoutItem>) :
        androidx.recyclerview.widget.RecyclerView.Adapter<PayoutAdapter.PayoutViewHolder>() {

        override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): PayoutViewHolder {
            val inflater = LayoutInflater.from(parent.context)
            val binding = ItemVendorPayoutBinding.inflate(inflater, parent, false)
            return PayoutViewHolder(binding)
        }

        override fun getItemCount(): Int = items.size

        override fun onBindViewHolder(holder: PayoutViewHolder, position: Int) {
            holder.bind(items[position])
        }

        class PayoutViewHolder(private val binding: ItemVendorPayoutBinding) :
            androidx.recyclerview.widget.RecyclerView.ViewHolder(binding.root) {

            fun bind(item: VendorPayoutItem) {
                val amount = item.vendor_amount ?: 0.0
                binding.tvPayoutAmount.text = "₦${String.format("%,.0f", amount)}"
                binding.tvPayoutStatus.text = item.status
                binding.tvPayoutDate.text = formatDate(item.settled_at)
            }

            private fun formatDate(dateString: String?): String {
                if (dateString.isNullOrEmpty()) return "Pending"
                return try {
                    val input = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault())
                    val date = input.parse(dateString.replace(Regex("\\.\\d+Z$"), ""))
                    SimpleDateFormat("dd MMM yyyy", Locale.getDefault()).format(date!!)
                } catch (_: Exception) {
                    dateString
                }
            }
        }
    }
}