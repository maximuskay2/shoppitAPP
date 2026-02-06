package com.shoppitplus.shoppit.consumer

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.lifecycle.lifecycleScope
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentEditProfileBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch


class EditProfile : Fragment() {
    private var _binding: FragmentEditProfileBinding? = null
    private val binding get() = _binding!!


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentEditProfileBinding.inflate(inflater, container, false)
        return binding.root


    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.backBtn.setOnClickListener {
            parentFragmentManager.popBackStack()
        }

        binding.btnSaveProfile.setOnClickListener {
            updateProfile()
        }
    }

    private fun updateProfile() {
        val name = binding.inputName.text.toString().trim()
        val phone = binding.inputPhone.text.toString().trim()
        val email = binding.inputEmail.text.toString().trim()

        if (name.isEmpty() || phone.isEmpty() || email.isEmpty()) {
            TopBanner.showError(
                requireActivity(),
                message = "Incomplete details",
                subMessage = "Please fill all fields"
            )
            return
        }

        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext())
                    .updateProfile(
                        fullName = name,
                        phone = phone,
                        email = email
                    )

                showLoading(false)

                if (response.success) {
                    val user = response.data.user

                    // Update UI
                    binding.inputName.setText(user.name)
                    binding.inputEmail.setText(user.email)
                    binding.inputPhone.setText(user.phone)

                    TopBanner.showSuccess(
                        requireActivity(),
                        message = "Profile updated",
                        subMessage = "Your changes have been saved"
                    )
                } else {
                    TopBanner.showError(
                        requireActivity(),
                        message = "Update failed",
                        subMessage = response.message
                    )
                }

            } catch (e: Exception) {
                showLoading(false)

                TopBanner.showError(
                    requireActivity(),
                    message = "Something went wrong",
                    subMessage = "Please try again"
                )
            }
        }
    }

    private fun showLoading(show: Boolean) {
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}


