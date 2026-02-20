package com.shoppitplus.shoppit.vendor

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
import com.shoppitplus.shoppit.databinding.FragmentVendorsLoginBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.LoginRequest
import kotlinx.coroutines.launch
import retrofit2.HttpException
import java.io.IOException
import kotlin.text.lowercase

class VendorsLogin : Fragment() {
   private  var _binding: FragmentVendorsLoginBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentVendorsLoginBinding.inflate(inflater, container, false)
        binding.submit.setOnClickListener {
            loginUser()
        }

        binding.register.setOnClickListener {
            findNavController().navigate(R.id.action_login_to_createAccount)
        }
        return binding.root
    }
    private fun loginUser() {
        val email = binding.emailEt.text.toString().trim()
        val password = binding.etPassword.text.toString().trim()

        if (email.isEmpty() || password.isEmpty()) {
            TopBanner.showError(
                requireActivity(),
                "Email and password required"
            )
            return
        }

        val request = LoginRequest(email, password)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            binding.submit.isEnabled = false

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.login(request)

                if (response.success && response.data != null) {
                    val loginData = response.data!!
                    val token = loginData.token
                    val role = loginData.role?.lowercase() ?: "user" // fallback

                    if (token.isNullOrBlank()) {
                        TopBanner.showError(requireActivity(), "Invalid token received")
                        return@launch
                    }


                    val prefs = requireContext().getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
                    prefs.edit().apply {
                        putString("auth_token", token)
                        putString("user_role", role)           // "vendor" or "user"/"customer"
                        putBoolean("is_logged_in", true)
                        apply()
                    }

                    TopBanner.showSuccess(requireActivity(), response.message ?: "Login successful!")

                    // Redirect based on role
                    val targetActivity = when (role) {
                        "vendor" -> VendorActivity::class.java
                        else -> CustomerActivity::class.java   // "user", "customer", or anything else
                    }

                    startActivity(Intent(requireContext(), targetActivity))
                    requireActivity().finishAffinity() // Clear back stack completely

                } else {
                    TopBanner.showError(
                        requireActivity(),
                        response.message ?: "Login failed"
                    )
                }

            } catch (e: HttpException) {
                val errorJson = e.response()?.errorBody()?.string()
                val message = try {
                    val json = org.json.JSONObject(errorJson ?: "{}")
                    json.optString("message", "Something went wrong")
                } catch (ex: Exception) {
                    "Something went wrong"
                }
                TopBanner.showError(requireActivity(), message)

            } catch (e: IOException) {
                TopBanner.showError(
                    requireActivity(),
                    "Check your internet connection"
                )

            } catch (e: Exception) {
                TopBanner.showError(
                    requireActivity(),
                    e.message ?: "Unexpected error"
                )

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
            val loadingOverlay = binding.loadingOverlay
            loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }


}