package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ItemWalletTransactionBinding
import com.shoppitplus.shoppit.utils.WalletTransaction

class WalletTransactionAdapter(
    private val transactions: List<WalletTransaction>
) : RecyclerView.Adapter<WalletTransactionAdapter.ViewHolder>() {

    inner class ViewHolder(val binding: ItemWalletTransactionBinding) : RecyclerView.ViewHolder(binding.root)

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): ViewHolder {
        val binding = ItemWalletTransactionBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return ViewHolder(binding)
    }

    override fun onBindViewHolder(holder: ViewHolder, position: Int) {
        val transaction = transactions[position]
        val showHeader = position == 0 ||
                transactions[position - 1].dateHeader != transaction.dateHeader

        with(holder.binding) {
            tvDateHeader.visibility = if (showHeader) View.VISIBLE else View.GONE
            if (showHeader) tvDateHeader.text = transaction.dateHeader

            tvNarration.text = transaction.narration
            tvTime.text = transaction.time
            tvStatus.text = transaction.status.uppercase()

            // Format amount (positive for funding)
            val prefix = if (transaction.type == "FUND_WALLET") "+" else "-"
            tvAmount.text = "$prefix₦${String.format("%,.0f", transaction.amount)}"
            tvAmount.setTextColor(
                if (transaction.type == "FUND_WALLET")
                    holder.itemView.context.getColor(R.color.primary_color)
                else
                    holder.itemView.context.getColor(android.R.color.holo_red_dark)
            )
        }
    }

    override fun getItemCount() = transactions.size
}