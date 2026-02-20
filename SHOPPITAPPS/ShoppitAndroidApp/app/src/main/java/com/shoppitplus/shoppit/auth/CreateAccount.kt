package com.shoppitplus.shoppit.auth

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.google.android.material.tabs.TabLayoutMediator
import com.shoppitplus.shoppit.vendor.VendorsCreateAccount
import com.shoppitplus.shoppit.adapter.TabAdapter
import com.shoppitplus.shoppit.consumer.ConsumerCreateAccount
import com.shoppitplus.shoppit.databinding.FragmentCreateAccountBinding


class CreateAccount : Fragment() {
   private var _binding : FragmentCreateAccountBinding? = null
    private val binding get() = _binding!!


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment

        _binding = FragmentCreateAccountBinding.inflate(inflater, container, false)

        val fragmentItems = listOf(
            "Consumer" to { ConsumerCreateAccount() },
            "Vendor" to { VendorsCreateAccount() }
        )

        val adapter = TabAdapter(
            childFragmentManager,
            viewLifecycleOwner.lifecycle,
            fragmentItems
        )

        binding.viewPager.adapter = adapter

        TabLayoutMediator(binding.tabLayout, binding.viewPager) { tab, position ->
            tab.text = fragmentItems[position].first
        }.attach()



        return binding.root
    }


}