package com.shoppitplus.shoppit.consumer

import android.graphics.PorterDuff
import android.os.Bundle
import android.util.Patterns
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.core.widget.doOnTextChanged
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentConsumerAccountDetailsBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.SetupProfileRequest
import kotlinx.coroutines.launch
import retrofit2.HttpException
import java.io.IOException

class ConsumerAccountDetails : Fragment() {

    private var _binding: FragmentConsumerAccountDetailsBinding? = null
    private val binding get() = _binding!!
    private val stateList = listOf("Akwa Ibom")
    private val cityListAkwaIbom = listOf("Uyo", "Eket", "Ikot-Ekpene")


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentConsumerAccountDetailsBinding.inflate(inflater, container, false)
        setupStateDropdown()
        setupCityDropdown()

        setupListeners()
        updateContinueState()
        return binding.root
    }
    private fun setupStateDropdown() = with(binding) {
        val adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_dropdown_item_1line,
            stateList
        )
        selectState.setAdapter(adapter)

        // Preselect "Akwa Ibom"
        selectState.setText("Akwa Ibom", false)

        // Make non-editable
        selectState.keyListener = null
        selectState.isFocusable = false
        selectState.isFocusableInTouchMode = false

        selectState.setOnClickListener {
            selectState.showDropDown()
        }

        // When user selects state → update city list
        selectState.setOnItemClickListener { _, _, _, _ ->
            setupCityDropdown()
        }
    }

    private fun setupCityDropdown() = with(binding) {
        val currentState = selectState.text.toString()

        val cityOptions = when (currentState) {
            "Akwa Ibom" -> cityListAkwaIbom
            else -> emptyList()
        }

        val adapter = ArrayAdapter(
            requireContext(),
            android.R.layout.simple_dropdown_item_1line,
            cityOptions
        )
        cityEt.setAdapter(adapter)

        // Non-editable
        cityEt.keyListener = null
        cityEt.isFocusable = false
        cityEt.isFocusableInTouchMode = false

        cityEt.setOnClickListener {
            cityEt.showDropDown()
        }

        // Reset if state changed
        cityEt.setText("", false)
    }


    private fun setupListeners() = with(binding) {
        // Text change listeners
        firstNameEt.doOnTextChanged { _, _, _, _ ->
            firstName.error = null
            updateContinueState()
        }
        surnameEt.doOnTextChanged { _, _, _, _ ->
            surname.error = null
            updateContinueState()
        }
        phoneNumber.doOnTextChanged { _, _, _, _ ->
            updateContinueState()
        }
        selectState.doOnTextChanged { _, _, _, _ ->
            state.error = null
            updateContinueState()
        }
        cityEt.doOnTextChanged { _, _, _, _ ->
            city.error = null
            updateContinueState()
        }
        addressEt.doOnTextChanged { _, _, _, _ ->
            address.error = null
            updateContinueState()
        }
        addressTwoEt.doOnTextChanged { _, _, _, _ ->
            addressTwo.error = null
            updateContinueState()
        }

        getStarted.setOnClickListener {
            if (validateFields()) onValidated()
        }
    }

    private fun onValidated() = with(binding) {

        val first = firstNameEt.text.toString().trim()
        val last = surnameEt.text.toString().trim()
        var phone = phoneNumber.text.toString().trim()
        val stateValue = selectState.text.toString().trim()
        val cityValue = cityEt.text.toString().trim()
        val address1 = addressEt.text.toString().trim()
        val address2 = addressTwoEt.text.toString().trim()

        val fullName = "$first $last"

        phone = phone.replace(" ", "")
        if (phone.startsWith("0")) {
            phone = phone.drop(1)
        }

        val request = SetupProfileRequest(
            fullName = fullName,
            phone = phone,
            state = stateValue,
            city = cityValue,
            address = address1,
            address2 = address2.ifEmpty { null }
        )

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            getStarted.isEnabled = false

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.setupProfile(request)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), response.message)
                    findNavController().navigate(R.id.action_consumerAccountDetails_to_consumerPassword)
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }

            } catch (e: HttpException) {

                val errorResponse = e.response()?.errorBody()?.string()
                var errorMessage = "Something went wrong"

                errorResponse?.let {
                    try {
                        val json = org.json.JSONObject(it)
                        errorMessage = json.optString("message", errorMessage)
                    } catch (_: Exception) {}
                }

                TopBanner.showError(requireActivity(), errorMessage)

            } catch (e: IOException) {
                TopBanner.showError(requireActivity(), "Check your internet connection")

            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), e.message ?: "Unexpected error occurred")

            } finally {
                showLoading(false)
                getStarted.isEnabled = true
            }
        }
    }



    private fun validateFields(): Boolean = with(binding) {
        var valid = true

        val firstName = firstNameEt.text?.toString()?.trim() ?: ""
        val surname = surnameEt.text?.toString()?.trim() ?: ""
        val phone = phoneNumber.text?.toString()?.trim() ?: ""
        val state = selectState.text?.toString()?.trim() ?: ""
        val city = cityEt.text?.toString()?.trim() ?: ""
        val addressLine = addressEt.text?.toString()?.trim() ?: ""

        // First name
        if (firstName.isEmpty()) {
            firstNameEt.error = "Please enter your first name"
            valid = false
        } else if (!isAlphaName(firstName)) {
            firstNameEt.error = "Invalid name"
            valid = false
        } else {
            firstNameEt.error = null
        }

        // Surname
        if (surname.isEmpty()) {
            surnameEt.error = "Please enter your last name"
            valid = false
        } else if (!isAlphaName(surname)) {
            surnameEt.error = "Invalid name"
            valid = false
        } else {
            surnameEt.error = null
        }

        // Phone number
        if (phone.isEmpty()) {
            Toast.makeText(requireContext(), "Please enter your phone number", Toast.LENGTH_SHORT)
                .show()
            phoneNumber.requestFocus()
            valid = false
        } else if (!isValidPhone(phone)) {
            Toast.makeText(requireContext(), "Enter a valid phone number", Toast.LENGTH_SHORT)
                .show()
            phoneNumber.requestFocus()
            valid = false
        }

        // State
        if (state.isEmpty() || state.equals("Select State", ignoreCase = true)) {
            this.state.error = "Please select a state"
            valid = false
        } else {
            this.state.error = null
        }

        // City
        if (city.isEmpty() || city.equals("Select Town/City", ignoreCase = true)) {
            this.city.error = "Please select a city"
            valid = false
        } else {
            this.city.error = null
        }

        // Address
        if (addressLine.isEmpty()) {
            address.error = "Please enter your address"
            valid = false
        } else if (addressLine.length < 3) {
            address.error = "Enter a valid address"
            valid = false
        } else {
            address.error = null
        }

        addressTwo.error = null // optional

        return valid
    }

    private fun updateContinueState() = with(binding) {
        val first = firstNameEt.text?.isNotBlank() == true
        val last = surnameEt.text?.isNotBlank() == true
        val phone = phoneNumber.text?.isNotBlank() == true
        val state = selectState.text?.isNotBlank() == true
        val city = cityEt.text?.isNotBlank() == true
        val address = addressEt.text?.isNotBlank() == true

        getStarted.isEnabled = first && last && phone && state && city && address
        getStarted.alpha = if (getStarted.isEnabled) 1.0f else 0.6f
    }

    private fun isAlphaName(name: String): Boolean {
        val pattern = Regex("^[A-Za-zÀ-ÖØ-öø-ÿ'\\- ]{2,50}\$")
        return pattern.matches(name)
    }

    private fun isValidPhone(phone: String): Boolean {
        val digitsOnly = phone.replace("""[\s\-\(\)]""".toRegex(), "")
        val pattern = Regex("^\\+?[0-9]{7,15}\$")
        return pattern.matches(digitsOnly) && Patterns.PHONE.matcher(digitsOnly).matches()
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
