package com.shoppitplus.shoppit.vendor

import android.graphics.Color
import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorPasswordBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.CreatePasswordRequest
import kotlinx.coroutines.launch
import retrofit2.HttpException
import java.io.IOException

class VendorPassword : Fragment() {

    private var _binding: FragmentVendorPasswordBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentVendorPasswordBinding.inflate(inflater, container, false)



        binding.arrowBack.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.continueButton.setOnClickListener {
            if (validatePassword()) {
                savePassword()
            } else {
                Toast.makeText(requireContext(), "Please fix the errors", Toast.LENGTH_SHORT).show()
            }
        }
        return binding.root
    }

    private fun validatePassword(): Boolean {


        val password = binding.etPassword.text.toString()
        val confirm = binding.etConfirmPassword.text.toString()

        var valid = true

        // Check password rules
        if (password.length < 8) {
            binding.tvMin8Chars.setTextColor(Color.RED)
            valid = false
        } else binding.tvMin8Chars.setTextColor(Color.GRAY)

        if (!password.any { !it.isLetterOrDigit() }) {
            binding.tvSpecialChar.setTextColor(Color.RED)
            valid = false
        } else binding.tvSpecialChar.setTextColor(Color.GRAY)

        if (!password.any { it.isDigit() }) {
            binding.tvNumber.setTextColor(Color.RED)
            valid = false
        } else binding.tvNumber.setTextColor(Color.GRAY)

        if (!password.any { it.isLetter() }) {
            binding.tvLetter.setTextColor(Color.RED)
            valid = false
        } else binding.tvLetter.setTextColor(Color.GRAY)

        // Confirm match
        if (password != confirm) {
            binding.matchText.text = "Passwords do not match"
            binding.matchText.setTextColor(Color.RED)
            valid = false
        }

        return valid
    }
    private fun savePassword() {
        val password = binding.etPassword.text.toString()

        val request = CreatePasswordRequest(password)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            binding.continueButton.isEnabled = false

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.createPassword(request)

                if (response.success) {
                    Toast.makeText(requireContext(), response.message, Toast.LENGTH_LONG).show()
                    findNavController().navigate(R.id.action_vendorPassword_to_login)
                } else {
                    Toast.makeText(requireContext(), response.message, Toast.LENGTH_LONG).show()
                }

            } catch (e: HttpException) {
                val errorBody = e.response()?.errorBody()?.string() ?: "Something went wrong"
                Toast.makeText(requireContext(), errorBody, Toast.LENGTH_LONG).show()

            } catch (e: IOException) {
                Toast.makeText(
                    requireContext(),
                    "Check your internet connection",
                    Toast.LENGTH_LONG
                ).show()

            } catch (e: Exception) {
                Toast.makeText(requireContext(), e.message ?: "Unexpected error", Toast.LENGTH_LONG)
                    .show()

            } finally {
                showLoading(false)
                binding.continueButton.isEnabled = true
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