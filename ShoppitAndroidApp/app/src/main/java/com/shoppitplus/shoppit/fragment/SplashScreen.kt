package com.shoppitplus.shoppit.fragment

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.activity.CustomerActivity
import com.shoppitplus.shoppit.activity.VendorActivity
import com.shoppitplus.shoppit.auth.AuthValidator
import com.shoppitplus.shoppit.databinding.FragmentSplashScreenBinding
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch

class SplashScreen : Fragment() {

    private var _binding: FragmentSplashScreenBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentSplashScreenBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        viewLifecycleOwner.lifecycleScope.launch {
            // Minimum splash time for branding
            delay(1600) // ← 1.6 seconds is usually enough in 2025–2026

            val context = requireContext()

            val isValid = AppPrefs.isLoggedIn(context) && AuthValidator.isValidSession(context)

            when {
                isValid -> {
                    navigateToMainActivity(context)
                }

                hasCompletedOnboarding(context) -> {
                    findNavController().navigate(R.id.action_splashScreen_to_login)
                }

                else -> {
                    findNavController().navigate(R.id.action_splashScreen_to_fragment_location_permission)
                }
            }
        }
    }

    private fun navigateToMainActivity(context: Context) {
        val role = AppPrefs.getUserType(context)?.trim()?.lowercase()

        val target = when (role) {
            "vendor" -> VendorActivity::class.java
            else -> CustomerActivity::class.java // "customer", "user", null, unknown...
        }

        startActivity(Intent(context, target).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        })

        requireActivity().finishAffinity()
    }

    private fun hasCompletedOnboarding(context: Context): Boolean {
        return context.getSharedPreferences("app_prefs", Context.MODE_PRIVATE)
            .getBoolean("location_permission_set", false)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}