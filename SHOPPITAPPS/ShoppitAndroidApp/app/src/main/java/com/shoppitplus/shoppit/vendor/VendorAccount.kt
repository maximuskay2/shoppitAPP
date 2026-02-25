package com.shoppitplus.shoppit.vendor

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.core.widget.doOnTextChanged
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorAccountBinding
import com.shoppitplus.shoppit.ui.TopBanner

class VendorAccount : Fragment(R.layout.fragment_vendor_account) {

    private var _binding: FragmentVendorAccountBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorAccountBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupListeners()
        updateButtonState()
    }

    private fun setupListeners() = with(binding) {
        // Text change listeners for real-time validation & button state
        listOf(
            firstNameEt,
            lastNameEt,
            phoneNumber,
            farmNameEt,
            referralCodeEt,
            cacNumberEt  // New CAC number field
        ).forEach { editText ->
            editText.doOnTextChanged { _, _, _, _ ->
                clearErrors()
                updateButtonState()
            }
        }

        // Continue button
        getStarted.setOnClickListener {
            if (validateFields()) {
                goToVendorDetails()
            } else {
                TopBanner.showError(
                    requireActivity(),
                    "Please complete all fields correctly"
                )
            }
        }

        // Back button
        arrowBack.setOnClickListener {
            findNavController().popBackStack()
        }
    }

    /* ---------------- VALIDATION ---------------- */

    private fun validateFields(): Boolean = with(binding) {
        var valid = true

        fun setError(
            layout: com.google.android.material.textfield.TextInputLayout,
            message: String
        ) {
            layout.error = message
            valid = false
        }

        clearErrors()

        if (firstNameEt.text.isNullOrBlank()) {
            setError(firstName, "First name is required")
        }

        if (lastNameEt.text.isNullOrBlank()) {
            setError(lastName, "Last name is required")
        }

        if (!isValidPhone(phoneNumber.text.toString())) {
            TopBanner.showError(requireActivity(), "Please enter a valid phone number")
            valid = false
        }

        if (farmNameEt.text.isNullOrBlank()) {
            setError(farmName, "Business name is required")
        }

        if (referralCodeEt.text.isNullOrBlank() || referralCodeEt.text!!.length < 8) {
            setError(referralCode, "Enter a valid Tax Identification Number (min 8 characters)")
        }

        if (cacNumberEt.text.isNullOrBlank()) {
            setError(cacNumber, "CAC Registration Number is required")
        } else if (cacNumberEt.text.toString().trim().length < 8) {
            setError(cacNumber, "CAC number must be at least 8 characters")
        }

        return valid
    }

    private fun clearErrors() = with(binding) {
        firstName.error = null
        lastName.error = null
        farmName.error = null
        referralCode.error = null
        cacNumber.error = null
    }

    /* ---------------- NAVIGATION ---------------- */

    private fun goToVendorDetails() {
        val bundle = Bundle().apply {
            val fullName = listOf(
                binding.firstNameEt.text.toString().trim(),
                binding.lastNameEt.text.toString().trim()
            ).filter { it.isNotEmpty() }
                .joinToString(" ")

            putString("fullName", fullName)

            val rawPhone = binding.phoneNumber.text.toString().trim()
            val cleanedPhone = cleanPhoneNumber(rawPhone)
            putString("phone", cleanedPhone)

            putString("businessName", binding.farmNameEt.text.toString().trim())
            putString("tin", binding.referralCodeEt.text.toString().trim())
            putString("cacNumber", binding.cacNumberEt.text.toString().trim().uppercase())
        }

        findNavController().navigate(
            R.id.action_vendorAccount_to_vendorDetails,
            bundle
        )
    }

    private fun cleanPhoneNumber(phone: String): String {
        var cleaned = phone.trim().replace(Regex("\\D"), "")

        if (cleaned.startsWith("234") && cleaned.length >= 13) {
            cleaned = cleaned.substring(3)
        }

        if (cleaned.startsWith("0") && cleaned.length == 11) {
            cleaned = cleaned.substring(1)
        }

        return if (cleaned.length == 10 && cleaned[0] in listOf('7', '8', '9')) {
            cleaned
        } else {
            phone.replace(Regex("\\D"), "")
        }
    }

    /* ---------------- UI STATE ---------------- */

    private fun updateButtonState() = with(binding) {
        val allFieldsFilled = firstNameEt.text?.isNotBlank() == true &&
                lastNameEt.text?.isNotBlank() == true &&
                phoneNumber.text?.isNotBlank() == true &&
                farmNameEt.text?.isNotBlank() == true &&
                referralCodeEt.text?.isNotBlank() == true &&
                cacNumberEt.text?.isNotBlank() == true

        getStarted.isEnabled = allFieldsFilled
        getStarted.alpha = if (allFieldsFilled) 1.0f else 0.5f
    }

    /* ---------------- HELPERS ---------------- */

    private fun isValidPhone(phone: String): Boolean {
        val cleaned = phone.trim().replace(Regex("\\D"), "")
        return when (cleaned.length) {
            11 -> cleaned.startsWith("0") && cleaned.substring(1)[0] in listOf('7', '8', '9')
            10 -> cleaned[0] in listOf('7', '8', '9')
            else -> false
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}