package com.shoppitplus.shoppit.ui.auth

import android.os.Bundle
import android.os.CountDownTimer
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.ActivityForgotPasswordBinding
import com.shoppitplus.shoppit.shared.models.ResetCodeRequest
import com.shoppitplus.shoppit.shared.models.ResetPasswordRequest
import com.shoppitplus.shoppit.shared.models.VerifyCodeRequest
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch
import java.io.IOException

class ForgotPasswordActivity : AppCompatActivity() {

    companion object {
        const val EXTRA_EMAIL = "extra_email"
    }

    private lateinit var binding: ActivityForgotPasswordBinding
    private var email: String = ""
    private val apiClient = ShoppitApiClient()
    private var resendTimer: CountDownTimer? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityForgotPasswordBinding.inflate(layoutInflater)
        setContentView(binding.root)

        email = intent.getStringExtra(EXTRA_EMAIL)?.trim().orEmpty()
        if (email.isNotEmpty()) {
            binding.emailInput.setText(email)
        }

        binding.backButton.setOnClickListener { onBackPressedDispatcher.onBackPressed() }
        binding.submitButton.setOnClickListener { sendResetCode() }
        binding.resetButton.setOnClickListener { resetPassword() }

        binding.resendOtpContainer.setOnClickListener { resendOtp() }
    }

    override fun onDestroy() {
        super.onDestroy()
        resendTimer?.cancel()
    }

    private fun sendResetCode() {
        email = binding.emailInput.text.toString().trim()
        if (email.isEmpty()) {
            TopBanner.showError(this, getString(R.string.snack_email_required))
            return
        }

        lifecycleScope.launch {
            try {
                binding.progressIndicator.visibility = View.VISIBLE
                binding.submitButton.isEnabled = false

                val response = apiClient.sendResetCode(ResetCodeRequest(email))

                binding.progressIndicator.visibility = View.GONE
                binding.submitButton.isEnabled = true

                if (response.success) {
                    TopBanner.showSuccess(this@ForgotPasswordActivity, getString(R.string.snack_reset_code_sent))
                    binding.otpLayout.visibility = View.VISIBLE
                    binding.submitButton.visibility = View.GONE
                    startResendTimer()
                } else {
                    TopBanner.showError(this@ForgotPasswordActivity, response.message)
                }
            } catch (e: IOException) {
                binding.progressIndicator.visibility = View.GONE
                binding.submitButton.isEnabled = true
                TopBanner.showError(this@ForgotPasswordActivity, getString(R.string.snack_network_error))
            } catch (e: Exception) {
                binding.progressIndicator.visibility = View.GONE
                binding.submitButton.isEnabled = true
                TopBanner.showError(this@ForgotPasswordActivity, e.localizedMessage ?: getString(R.string.snack_something_wrong))
            }
        }
    }

    private fun verifyCodeThenReset() {
        val code = binding.otpInput.text.toString().trim()
        if (code.isEmpty()) {
            TopBanner.showError(this, getString(R.string.snack_reset_code_required))
            return
        }

        lifecycleScope.launch {
            try {
                binding.progressIndicator.visibility = View.VISIBLE
                binding.resetButton.isEnabled = false

                val response = apiClient.verifyResetCode(VerifyCodeRequest(email, code))

                if (response.success) {
                    performResetPassword()
                } else {
                    binding.progressIndicator.visibility = View.GONE
                    binding.resetButton.isEnabled = true
                    TopBanner.showError(this@ForgotPasswordActivity, response.message)
                }
            } catch (e: Exception) {
                binding.progressIndicator.visibility = View.GONE
                binding.resetButton.isEnabled = true
                TopBanner.showError(this@ForgotPasswordActivity, e.localizedMessage ?: getString(R.string.snack_something_wrong))
            }
        }
    }

    private fun performResetPassword() {
        val newPassword = binding.newPasswordInput.text.toString()
        val confirmPassword = binding.confirmPasswordInput.text.toString()

        if (newPassword.length < 8) {
            binding.progressIndicator.visibility = View.GONE
            binding.resetButton.isEnabled = true
            TopBanner.showError(this, getString(R.string.snack_password_min_length))
            return
        }
        if (newPassword != confirmPassword) {
            binding.progressIndicator.visibility = View.GONE
            binding.resetButton.isEnabled = true
            TopBanner.showError(this, getString(R.string.snack_password_mismatch))
            return
        }

        lifecycleScope.launch {
            try {
                val response = apiClient.resetPassword(ResetPasswordRequest(email, newPassword, confirmPassword))

                binding.progressIndicator.visibility = View.GONE
                binding.resetButton.isEnabled = true

                if (response.success) {
                    TopBanner.showSuccess(this@ForgotPasswordActivity, getString(R.string.snack_password_reset_success))
                    finish()
                } else {
                    TopBanner.showError(this@ForgotPasswordActivity, response.message)
                }
            } catch (e: Exception) {
                binding.progressIndicator.visibility = View.GONE
                binding.resetButton.isEnabled = true
                TopBanner.showError(this@ForgotPasswordActivity, e.localizedMessage ?: getString(R.string.snack_something_wrong))
            }
        }
    }

    private fun resetPassword() {
        verifyCodeThenReset()
    }

    private fun startResendTimer() {
        binding.resendOtpContainer.isClickable = false
        binding.resendOtpButton.alpha = 0.5f
        resendTimer?.cancel()

        resendTimer = object : CountDownTimer(30000, 1000) {
            override fun onTick(millisUntilFinished: Long) {
                val seconds = millisUntilFinished / 1000
                binding.resendOtpTimer.text = String.format("(Resend in 00:%02d)", seconds)
            }

            override fun onFinish() {
                binding.resendOtpContainer.isClickable = true
                binding.resendOtpButton.alpha = 1f
                binding.resendOtpTimer.text = ""
            }
        }.start()
    }

    private fun resendOtp() {
        if (!binding.resendOtpContainer.isClickable) return
        sendResetCode()
    }
}
