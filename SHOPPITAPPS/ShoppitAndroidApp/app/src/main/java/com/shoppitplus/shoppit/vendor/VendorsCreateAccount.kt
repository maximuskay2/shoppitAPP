package com.shoppitplus.shoppit.vendor

import android.content.Context
import android.graphics.PorterDuff
import android.os.Bundle
import android.util.Patterns
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorsCreateAccountBinding
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.shared.models.RegistrationRequest
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch
import java.io.IOException


class VendorsCreateAccount : Fragment() {
    private var _binding: FragmentVendorsCreateAccountBinding? = null
    private val binding get() = _binding!!

    // Use the shared KMP API Client
    private val apiClient = ShoppitApiClient()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        _binding = FragmentVendorsCreateAccountBinding.inflate(inflater, container, false)

        binding.submit.setOnClickListener {
            if (!Patterns.EMAIL_ADDRESS.matcher(binding.emailEt.text.toString()).matches()) {
                binding.emailEt.error = "Invalid Email Address"
            } else {
                verifyUser()
            }
        }

        binding.login.setOnClickListener {
            findNavController().navigate(R.id.action_createAccount_to_login)
        }

        return binding.root
    }

    private fun verifyUser() {
        val email = binding.emailEt.text.toString().trim()

        if (email.isEmpty()) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_email_required))
            return
        }

        // Using Shared Model
        val request = RegistrationRequest(email = email)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)

            try {
                // Calling Shared KMP Client
                val response = apiClient.register(request)

                showLoading(false)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_registration_success))

                    val token = response.data?.token
                    token?.let {
                        val sharedPreferences =
                            requireContext().getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
                        sharedPreferences.edit().putString("auth_token", it).apply()
                    }

                    saveEmailInPreferences(email)
                    findNavController().navigate(R.id.action_createAccount_to_vendorsEmailVerify)

                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }

            } catch (e: IOException) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))

            } catch (e: Exception) {
                showLoading(false)
                val msg = e.message.orEmpty()
                val isUserExists = msg.contains("already", ignoreCase = true) ||
                    msg.contains("taken", ignoreCase = true) ||
                    msg.contains("exist", ignoreCase = true) ||
                    msg.contains("registered", ignoreCase = true)
                if (isUserExists) {
                    TopBanner.showError(requireActivity(), getString(R.string.snack_email_exists))
                    findNavController().navigate(R.id.action_createAccount_to_login)
                } else {
                    TopBanner.showError(requireActivity(), msg.ifEmpty { getString(R.string.snack_something_wrong) })
                }
            }
        }
    }

    private fun showLoading(show: Boolean) {
        binding.progressBar.apply {
            indeterminateDrawable.setColorFilter(
                ContextCompat.getColor(requireContext(), R.color.primary_color),
                PorterDuff.Mode.SRC_IN
            )
            binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }

    private fun saveEmailInPreferences(email: String) {
        val sharedPreferences = activity?.getSharedPreferences("info", Context.MODE_PRIVATE)
        sharedPreferences?.edit()?.apply {
            putString("emailAddress", email)
            apply()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
