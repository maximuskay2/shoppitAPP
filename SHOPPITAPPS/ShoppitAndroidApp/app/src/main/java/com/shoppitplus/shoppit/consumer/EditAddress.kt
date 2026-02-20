package com.shoppitplus.shoppit.consumer

import android.graphics.PorterDuff
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentEditAddressBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.AddAddressRequest
import kotlinx.coroutines.launch

class EditAddress : Fragment() {
    private var _binding: FragmentEditAddressBinding? = null
    private val binding get() = _binding!!

    private val stateList = listOf("Akwa Ibom")
    private val cityListAkwaIbom = listOf("Uyo", "Eket", "Ikot Ekpene", "Abak", "Ikot Abasi")

    private var selectedState: String = ""
    private var selectedCity: String = ""



    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentEditAddressBinding.inflate(inflater, container, false)


        return binding.root
    }
    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupStateDropdown()
        setupCityDropdown()

        binding.arrowBack.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.getStarted.setOnClickListener {
            submitAddress()
        }
    }

    private fun setupStateDropdown() {
        val stateAdapter = ArrayAdapter(requireContext(), R.layout.dropdown_item, stateList)
        binding.selectState.setAdapter(stateAdapter)

        binding.selectState.setOnItemClickListener { _, _, position, _ ->
            selectedState = stateList[position]
            // Reset city when state changes
            selectedCity = ""
            binding.cityEt.text = null
            setupCityDropdown() // Update city list
        }
    }

    private fun setupCityDropdown() {
        val cityList = when (selectedState) {
            "Akwa Ibom" -> cityListAkwaIbom
            else -> emptyList()
        }

        val cityAdapter = ArrayAdapter(requireContext(), R.layout.dropdown_item, cityList)
        binding.cityEt.setAdapter(cityAdapter)

        binding.cityEt.setOnItemClickListener { _, _, position, _ ->
            selectedCity = cityList[position]
        }
    }

    private fun submitAddress() {
        val address1 = binding.addressEt.text.toString().trim()
        val address2 = binding.addressTwoEt.text.toString().trim().ifEmpty { null }
        val state = selectedState
        val city = selectedCity

        if (address1.isEmpty()) {
            TopBanner.showError(requireActivity(), "Please enter your address")
            return
        }
        if (state.isEmpty()) {
            TopBanner.showError(requireActivity(), "Please select a state")
            return
        }
        if (city.isEmpty()) {
            TopBanner.showError(requireActivity(), "Please select a city")
            return
        }

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                showLoading(true)
                val api = RetrofitClient.instance(requireContext())
                val request = AddAddressRequest(
                    address = address1,
                    address_2 = address2,
                    city = city,
                    state = state,
                    is_default = 1
                )

                val response = api.addAddress(request)
                showLoading(false)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), "Address saved successfully")
                    findNavController().popBackStack() // Go back after success
                } else {
                    TopBanner.showError(requireActivity(), response.message ?: "Failed to save address")
                }
            } catch (e: Exception) {
                showLoading(false)
                TopBanner.showError(requireActivity(), "Network error. Try again.")
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