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
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.RegistrationRequest
import kotlinx.coroutines.launch
import org.json.JSONObject
import java.io.IOException


class VendorsCreateAccount : Fragment() {
    private var _binding: FragmentVendorsCreateAccountBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment

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
            TopBanner.showError(requireActivity(), "Email is required")
            return
        }

        val request = RegistrationRequest(email = email)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.register(request)

                showLoading(false)

                if (response.isSuccessful) {

                    val message = response.body()?.message ?: "Success"
                    TopBanner.showSuccess(requireActivity(), message)
                    val token = response.body()?.data?.token
                    token?.let {
                        val sharedPreferences =
                            requireContext().getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
                        sharedPreferences.edit().putString("auth_token", it).apply()
                    }

                    saveEmailInPreferences(email)
                    findNavController().navigate(R.id.action_createAccount_to_vendorsEmailVerify)

                } else {
                    val errorBody = response.errorBody()?.string()
                    val backendMessage = try {
                        val json = JSONObject(errorBody ?: "")
                        json.optString("message", "Something went wrong")
                    } catch (e: Exception) {
                        "Something went wrong"
                    }

                    TopBanner.showError(requireActivity(), backendMessage)
                }

            } catch (e: IOException) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Check your internet connection")

            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), e.localizedMessage ?: "Unexpected error")
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

    private fun saveEmailInPreferences(email: String) {
        val sharedPreferences = activity?.getSharedPreferences("info", Context.MODE_PRIVATE)
        sharedPreferences?.edit()?.apply {
            putString("emailAddress", email)
            apply()
        }
    }

}