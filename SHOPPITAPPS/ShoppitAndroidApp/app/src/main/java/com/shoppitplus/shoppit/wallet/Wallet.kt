package com.shoppitplus.shoppit.wallet

import android.app.AlertDialog
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.EditText
import androidx.appcompat.widget.AppCompatButton
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.WalletTransactionAdapter
import com.shoppitplus.shoppit.databinding.FragmentWalletBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.DepositRequest
import com.shoppitplus.shoppit.utils.WalletTransaction
import kotlinx.coroutines.launch

class Wallet : Fragment() {

    private var _binding: FragmentWalletBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentWalletBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.backButton.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.tvAddMoney.setOnClickListener {
            showAddMoneyDialog()
        }
        binding.swipeRefresh.setOnRefreshListener {
            loadWalletData()
            binding.swipeRefresh.isRefreshing = false
        }

        loadWalletData()
    }

    private fun loadWalletData() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)  // ← Show loading at start

                // Get balance
                val balanceResponse = RetrofitClient.instance(requireContext()).getWalletBalance()
                if (balanceResponse.success) {
                    val balance = balanceResponse.data.balance
                    binding.tvBalance.text = "₦${String.format("%,.0f", balance)}"
                }

                // Get transactions
                val txResponse = RetrofitClient.instance(requireContext()).getWalletTransactions()
                if (txResponse.success) {
                    val transactions = mutableListOf<WalletTransaction>()
                    txResponse.data.data.forEach { (date, list) ->
                        list.forEach { tx ->
                            transactions.add(
                                WalletTransaction(
                                    dateHeader = date,
                                    type = tx.type,
                                    amount = tx.amount,
                                    status = tx.status,
                                    narration = tx.description,
                                    time = tx.date.split(" ").last(),
                                    fee = tx.fee
                                )
                            )
                        }
                    }
                    binding.transactionsRecycler.adapter = WalletTransactionAdapter(transactions)
                    binding.transactionsRecycler.layoutManager =
                        LinearLayoutManager(requireContext())
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Failed to load wallet")
            } finally {
                showLoading(false)  // ← Always hide loading
            }
        }
    }

    private fun showAddMoneyDialog() {
        val dialogView = layoutInflater.inflate(R.layout.dialog_add_money, null)
        val dialog = AlertDialog.Builder(requireContext())
            .setView(dialogView)
            .setCancelable(true)
            .create()

        val etAmount = dialogView.findViewById<EditText>(R.id.etAmount)
        val btnContinue = dialogView.findViewById<AppCompatButton>(R.id.btnContinue)

        btnContinue.setOnClickListener {
            val amountStr = etAmount.text.toString().trim()
            if (amountStr.isEmpty()) {
                etAmount.error = "Enter amount"
                return@setOnClickListener
            }

            val amount = amountStr.toIntOrNull() ?: 0
            if (amount < 100) {
                etAmount.error = "Minimum amount is ₦100"
                return@setOnClickListener
            }

            fundWallet(amount, dialog)
        }

        dialog.show()
    }

    private fun fundWallet(amount: Int, dialog: AlertDialog) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)

                val request = DepositRequest(amount)
                val response = RetrofitClient.instance(requireContext()).depositToWallet(request)

                if (response.success && response.data != null) {
                    dialog.dismiss()

                    // Use manual Bundle to pass URL
                    val bundle = Bundle().apply {
                        putString("url", response.data.authorization_url)
                    }

                    findNavController().navigate(
                        R.id.action_wallet_to_fragment_paystack_webview,
                        bundle
                    )

                    TopBanner.showSuccess(requireActivity(), "Redirecting to complete payment...")
                } else {
                    TopBanner.showError(requireActivity(), response.message ?: "Failed to initiate deposit")
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Network error")
            } finally {
                showLoading(false)
            }
        }
    }

    private fun showLoading(show: Boolean) {
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}