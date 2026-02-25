package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.content.Intent
import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.activity.CustomerActivity
import com.shoppitplus.shoppit.activity.VendorActivity
import com.shoppitplus.shoppit.databinding.FragmentConsumerLoginBinding
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.shared.models.LoginRequest
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.ui.auth.ForgotPasswordActivity
import kotlinx.coroutines.launch
import java.io.IOException

class ConsumerLogin : Fragment() {
    private var _binding: FragmentConsumerLoginBinding? = null
    private val binding get() = _binding!!

    // Use the shared KMP API Client
    private val apiClient = ShoppitApiClient()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        _binding = FragmentConsumerLoginBinding.inflate(inflater, container, false)

        binding.submit.setOnClickListener {
            loginUser()
        }

        binding.register.setOnClickListener {
            findNavController().navigate(R.id.action_login_to_createAccount)
        }

        binding.forgetPassword.setOnClickListener {
            val intent = Intent(requireContext(), ForgotPasswordActivity::class.java).apply {
                putExtra(ForgotPasswordActivity.EXTRA_EMAIL, binding.emailEt.text.toString().trim())
            }
            startActivity(intent)
        }

        return binding.root
    }

    private fun loginUser() {
        val email = binding.emailEt.text.toString().trim()
        val password = binding.etPassword.text.toString().trim()

        if (email.isEmpty() || password.isEmpty()) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_credentials_required))
            return
        }

        // Using Shared Model
        val request = LoginRequest(email, password)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            binding.submit.isEnabled = false

            try {
                // Calling Shared KMP Client
                val response = apiClient.login(request)

                if (response.success && response.data != null) {
                    val loginData = response.data!!
                    val token = loginData.token
                    val role = loginData.role.lowercase()

                    val prefs = requireContext().getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
                    prefs.edit().apply {
                        putString("auth_token", token)
                        putString("user_role", role)
                        putBoolean("is_logged_in", true)
                        apply()
                    }

                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_login_success))

                    val targetActivity = when (role) {
                        "vendor" -> VendorActivity::class.java
                        else -> CustomerActivity::class.java
                    }

                    startActivity(Intent(requireContext(), targetActivity))
                    requireActivity().finishAffinity()

                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }

            } catch (e: IOException) {
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), e.message ?: getString(R.string.snack_something_wrong))
            } finally {
                showLoading(false)
                binding.submit.isEnabled = true
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

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
