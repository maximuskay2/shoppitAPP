package com.shoppitplus.shoppit.fragment

import android.content.Context
import android.content.SharedPreferences
import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentLocationPermissionBinding


class fragment_location_permission : Fragment() {
    private var _binding: FragmentLocationPermissionBinding? = null
    private val binding get() = _binding!!

    private val PREFS_NAME = "app_prefs"
    private val KEY_LOCATION_PERMISSION = "location_permission_set"

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentLocationPermissionBinding.inflate(inflater, container, false)

        val sharedPref = requireContext().getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

        // If user already chose, skip this screen
        if (sharedPref.getBoolean(KEY_LOCATION_PERMISSION, false)) {
            findNavController().navigate(R.id.action_fragment_location_permission_to_onboardingScreen)
        }

        binding.btnAllow.setOnClickListener {
            saveLocationChoice(sharedPref, true)
            findNavController().navigate(R.id.action_fragment_location_permission_to_onboardingScreen)
        }

        binding.btnDeny.setOnClickListener {
            saveLocationChoice(sharedPref, true)
            findNavController().navigate(R.id.action_fragment_location_permission_to_onboardingScreen)
        }


        return binding.root



}

    private fun saveLocationChoice(prefs: SharedPreferences, value: Boolean) {
        prefs.edit().putBoolean(KEY_LOCATION_PERMISSION, value).apply()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
    }