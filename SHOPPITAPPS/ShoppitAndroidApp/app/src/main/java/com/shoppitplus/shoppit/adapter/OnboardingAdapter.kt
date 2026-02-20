package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.ViewGroup
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.databinding.OnboardingItemBinding
import com.shoppitplus.shoppit.utils.OnboardingItem

class OnboardingAdapter(
    private val items: List<OnboardingItem>,

) : RecyclerView.Adapter<OnboardingAdapter.OnboardingViewHolder>() {

    inner class OnboardingViewHolder(private val binding: OnboardingItemBinding) :
        RecyclerView.ViewHolder(binding.root) {

        fun bind(item: OnboardingItem) {
            binding.onboardingImage.setImageResource(item.imageRes)

        }
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): OnboardingViewHolder {
        val binding =
            OnboardingItemBinding.inflate(LayoutInflater.from(parent.context), parent, false)
        return OnboardingViewHolder(binding)
    }

    override fun onBindViewHolder(holder: OnboardingViewHolder, position: Int) {
        holder.bind(items[position])
    }

    override fun getItemCount() = items.size
}