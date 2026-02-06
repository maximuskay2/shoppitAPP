package com.shoppitplus.shoppit.vendor

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorDetailsBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.RequestBody.Companion.toRequestBody

class VendorDetails : Fragment() {

    private var _binding: FragmentVendorDetailsBinding? = null
    private val binding get() = _binding!!

    // Data from previous screen
    private lateinit var fullName: String
    private lateinit var phone: String
    private lateinit var businessName: String
    private lateinit var tin: String
    private lateinit var cacNumber: String  // Now a string

    // State & City Lists
    private val stateList = listOf("Akwa Ibom")
    private val cityListAkwaIbom = listOf("Uyo", "Eket", "Ikot Ekpene")

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentVendorDetailsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        readArguments()
        setupStateDropdown()
        setupCityDropdown()
        setupListeners()
    }

    private fun readArguments() {
        val args = requireArguments()

        fullName = args.getString("fullName", "")
        phone = args.getString("phone", "")
        businessName = args.getString("businessName", "")
        tin = args.getString("tin", "")
        cacNumber = args.getString("cacNumber", "")

        // Optional: validate that cacNumber is not empty
        if (cacNumber.isEmpty()) {
            TopBanner.showError(requireActivity(), "CAC number missing. Please go back.")
            findNavController().popBackStack()
        }
    }

    private fun setupStateDropdown() = with(binding) {
        val adapter =
            ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, stateList)
        selectState.setAdapter(adapter)
        selectState.setText("Akwa Ibom", false)
        selectState.keyListener = null
        selectState.isFocusable = false
        selectState.setOnClickListener { selectState.showDropDown() }
        selectState.setOnItemClickListener { _, _, _, _ -> setupCityDropdown() }
    }

    private fun setupCityDropdown() = with(binding) {
        val cityOptions = when (selectState.text.toString()) {
            "Akwa Ibom" -> cityListAkwaIbom
            else -> emptyList()
        }

        val adapter =
            ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, cityOptions)
        cityEt.setAdapter(adapter)
        cityEt.keyListener = null
        cityEt.isFocusable = false
        cityEt.setOnClickListener {
            if (cityOptions.isNotEmpty()) cityEt.showDropDown()
            else TopBanner.showError(requireActivity(), "No cities available")
        }

        if (cityOptions.isNotEmpty()) cityEt.setText("", false)
    }

    private fun setupListeners() = with(binding) {
        arrowBack.setOnClickListener { findNavController().popBackStack() }

        getStarted.setOnClickListener {
            clearErrors()
            if (validateInputs()) {
                submitVendorDetails()
            }
        }
    }

    private fun validateInputs(): Boolean = with(binding) {
        var isValid = true

        if (addressEt.text.toString().trim().isEmpty()) {
            address.error = "Please enter your address"
            isValid = false
        }

        if (selectState.text.toString().isEmpty()) {
            state.error = "State is required"
            isValid = false
        }

        if (cityEt.text.toString().isEmpty()) {
            city.error = "Please select your city"
            isValid = false
        }

        return isValid
    }

    private fun clearErrors() = with(binding) {
        address.error = null
        state.error = null
        city.error = null
    }

    /* ------------------- API SUBMISSION ------------------- */

    private fun submitVendorDetails() = with(binding) {
        getStarted.isEnabled = false
        getStarted.text = "Submitting..."

        val state = selectState.text.toString()
        val city = cityEt.text.toString()
        val address = addressEt.text.toString().trim()

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).setupVendorProfile(
                    fullName = fullName.toRequestBody("text/plain".toMediaTypeOrNull()),
                    tin = tin.toRequestBody("text/plain".toMediaTypeOrNull()),
                    phone = phone.toRequestBody("text/plain".toMediaTypeOrNull()),
                    businessName = businessName.toRequestBody("text/plain".toMediaTypeOrNull()),
                    state = state.toRequestBody("text/plain".toMediaTypeOrNull()),
                    city = city.toRequestBody("text/plain".toMediaTypeOrNull()),
                    address = address.toRequestBody("text/plain".toMediaTypeOrNull()),
                    cac = cacNumber.toRequestBody("text/plain".toMediaTypeOrNull())
                )

                if (response.success) {
                    TopBanner.showSuccess(
                        requireActivity(),
                        response.message ?: "Profile setup successful!"
                    )
                    findNavController().navigate(R.id.action_vendorDetails_to_vendorPassword)
                } else {
                    TopBanner.showError(requireActivity(), response.message ?: "Setup failed")
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), e.message ?: "An error occurred")
            } finally {
                getStarted.isEnabled = true
                getStarted.text = "Continue"
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}