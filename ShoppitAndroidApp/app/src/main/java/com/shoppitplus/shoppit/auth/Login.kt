package com.shoppitplus.shoppit.auth

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.google.android.material.tabs.TabLayoutMediator
import com.shoppitplus.shoppit.vendor.VendorsLogin
import com.shoppitplus.shoppit.adapter.TabAdapter
import com.shoppitplus.shoppit.consumer.ConsumerLogin
import com.shoppitplus.shoppit.databinding.FragmentLoginBinding


class Login : Fragment() {
    private var _binding : FragmentLoginBinding? = null
    private val binding get() = _binding!!


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment

        _binding = FragmentLoginBinding.inflate(inflater, container, false)

        val fragmentItems = listOf(
            "Consumer" to { ConsumerLogin() },
            "Vendor" to { VendorsLogin() }
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
    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }



}