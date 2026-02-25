package com.shoppitplus.shoppit.fragment

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import com.shoppitplus.shoppit.databinding.FragmentDeliveryAddressBinding


class DeliveryAddress : Fragment() {
   private var _binding : FragmentDeliveryAddressBinding? = null
    private val binding get() = _binding!!


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment

        _binding = FragmentDeliveryAddressBinding.inflate(inflater,container,false)

        binding.closeIcon.setOnClickListener {
            requireActivity().onBackPressedDispatcher.onBackPressed()
        }
        return binding.root
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}