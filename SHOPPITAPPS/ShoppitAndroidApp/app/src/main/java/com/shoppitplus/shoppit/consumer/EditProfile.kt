package com.shoppitplus.shoppit.consumer

import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.lifecycle.lifecycleScope
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentEditProfileBinding
import com.shoppitplus.shoppit.shared.models.UpdateProfileRequest
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class EditProfile : Fragment() {
    private var _binding: FragmentEditProfileBinding? = null
    private val binding get() = _binding!!
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentEditProfileBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
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
            TopBanner.showError(requireActivity(), getString(R.string.snack_incomplete_details))
            return
        }

        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = apiClient.updateProfile(
                    authToken!!,
                    UpdateProfileRequest(fullName = name, phone = phone, email = email)
                )

                showLoading(false)

                if (response.success && response.data != null) {
                    val user = response.data!!.user

                    binding.inputName.setText(user.name)
                    binding.inputEmail.setText(user.email)
                    binding.inputPhone.setText(user.phone)

                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_profile_updated))
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }

            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), getString(R.string.snack_something_wrong))
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
