package com.shoppitplus.shoppit.vendor

import android.content.Context
import android.graphics.PorterDuff
import android.os.Bundle
import android.os.CountDownTimer
import android.text.Editable
import android.text.TextWatcher
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.EditText
import androidx.core.content.ContextCompat
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentVendorsEmailVerifyBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.ResendOtpRequest
import com.shoppitplus.shoppit.utils.VerifyOtpRequest
import kotlinx.coroutines.launch
import retrofit2.HttpException
import java.io.IOException

class VendorsEmailVerify : Fragment() {
    private var _binding: FragmentVendorsEmailVerifyBinding? = null
    private val binding get() = _binding!!

    private var countDownTimer: CountDownTimer? = null
    private lateinit var otpInputs: List<EditText>


    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentVendorsEmailVerifyBinding.inflate(inflater, container, false)
        val sharedPreferences = activity?.getSharedPreferences("info", Context.MODE_PRIVATE)
        val userEmailDisplay = sharedPreferences?.getString("emailAddress", "").orEmpty()
        binding.tvEmail.text = userEmailDisplay


        binding.tvResend.setOnClickListener { resendOtp() }
        binding.resendContainer.setOnClickListener { resendOtp() }

        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        otpInputs = listOf(
            binding.otp1, binding.otp2, binding.otp3,
            binding.otp4, binding.otp5, binding.otp6
        )

        setupOtpInputs()

        binding.btnVerify.setOnClickListener { verifyOtp() }
        binding.backBtn.setOnClickListener { requireActivity().onBackPressedDispatcher.onBackPressed() }

        startTimer()
    }

    private fun verifyOtp() {
        val otp = getOtp()
        if (!validateOtp(otp)) return

        val sharedPreferences = activity?.getSharedPreferences("info", Context.MODE_PRIVATE)
        val email = sharedPreferences?.getString("emailAddress", "").orEmpty()

        if (email.isEmpty()) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_email_not_found))
            return
        }

        val request = VerifyOtpRequest(email = email, verification_code = otp)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            binding.btnVerify.isEnabled = false

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.verifyRegisterOtp(request)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_otp_verify_success))
                    findNavController().navigate(R.id.action_vendorsEmailVerify_to_vendorAccount)
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                }

            } catch (e: HttpException) {
                val error = e.response()?.errorBody()?.string() ?: getString(R.string.snack_something_wrong)
                TopBanner.showError(requireActivity(), error)

            } catch (e: IOException) {
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))

            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), e.message ?: getString(R.string.snack_something_wrong))

            } finally {
                showLoading(false)
                binding.btnVerify.isEnabled = true
            }
        }
    }


    private fun getOtp(): String = otpInputs.joinToString("") { it.text.toString().trim() }


    private fun validateOtp(otp: String): Boolean {
        return if (otp.length < 6) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_otp_enter_complete))
            false
        } else true
    }

    private fun setupOtpInputs() {
        otpInputs.forEachIndexed { index, editText ->

            editText.addTextChangedListener(object : TextWatcher {
                override fun afterTextChanged(s: Editable?) {
                    if (s?.length == 1) {
                        if (index < otpInputs.size - 1) {
                            otpInputs[index + 1].requestFocus()
                        } else {
                            editText.clearFocus()
                            verifyOtp() // Auto verify on last input (optional)
                        }
                    } else if (s?.isEmpty() == true && index > 0) {
                        otpInputs[index - 1].requestFocus()
                    }
                }

                override fun beforeTextChanged(
                    s: CharSequence?,
                    start: Int,
                    count: Int,
                    after: Int
                ) {
                }

                override fun onTextChanged(s: CharSequence?, start: Int, before: Int, count: Int) {}
            })

            editText.setOnFocusChangeListener { v, hasFocus ->
                if (hasFocus) {
                    editText.selectAll()
                }
            }

            editText.setOnKeyListener { _, _, _ ->
                if (editText.text.length > 1) {
                    editText.setText(editText.text.last().toString())
                    editText.setSelection(1)
                }
                false
            }
        }
    }

    private fun startTimer() {
        binding.tvResend.isEnabled = false
        countDownTimer?.cancel()

        countDownTimer = object : CountDownTimer(30000, 1000) {
            override fun onTick(millisUntilFinished: Long) {
                _binding?.let {
                    val seconds = millisUntilFinished / 1000
                    it.tvTimer.text = String.format("(00:%02d)", seconds)
                }
            }

            override fun onFinish() {
                _binding?.let {
                    it.tvTimer.text = ""
                    it.tvResend.isEnabled = true
                }
            }
        }.start()
    }

    private fun resendOtp() {
        if (!binding.tvResend.isEnabled) return // Wait for timer to finish

        val sharedPreferences = activity?.getSharedPreferences("info", Context.MODE_PRIVATE)
        val email = sharedPreferences?.getString("emailAddress", "").orEmpty()

        if (email.isEmpty()) {
            TopBanner.showError(requireActivity(), getString(R.string.snack_email_not_found))
            return
        }

        val request = ResendOtpRequest(email)

        viewLifecycleOwner.lifecycleScope.launch {
            showLoading(true)
            binding.tvResend.isEnabled = false

            try {
                val api = RetrofitClient.instance(requireContext())
                val response = api.resendRegisterOtp(request)

                if (response.success) {
                    TopBanner.showSuccess(requireActivity(), getString(R.string.snack_otp_resend_success))
                    startTimer()
                } else {
                    TopBanner.showError(requireActivity(), response.message)
                    binding.tvResend.isEnabled = true
                }

            } catch (e: HttpException) {
                val errorBody = e.response()?.errorBody()?.string() ?: getString(R.string.snack_something_wrong)
                TopBanner.showError(requireActivity(), errorBody)
                binding.tvResend.isEnabled = true

            } catch (e: IOException) {
                TopBanner.showError(requireActivity(), getString(R.string.snack_network_error))
                binding.tvResend.isEnabled = true

            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), e.message ?: getString(R.string.snack_something_wrong))
                binding.tvResend.isEnabled = true

            } finally {
                showLoading(false)
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
        countDownTimer?.cancel()
        _binding = null
    }


}