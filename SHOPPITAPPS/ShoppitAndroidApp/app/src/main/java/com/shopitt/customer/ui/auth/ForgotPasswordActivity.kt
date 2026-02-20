package com.shopitt.customer.ui.auth

import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.button.MaterialButton
import com.google.android.material.progressindicator.CircularProgressIndicator
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.models.RetrofitClient
import kotlinx.coroutines.launch

class ForgotPasswordActivity : AppCompatActivity() {

    private lateinit var emailInput: TextInputEditText
    private lateinit var emailLayout: TextInputLayout
    private lateinit var submitButton: MaterialButton
    private lateinit var progressIndicator: CircularProgressIndicator
    private lateinit var backButton: View

    // For OTP verification stage
    private lateinit var otpLayout: View
    private lateinit var otpInput: TextInputEditText
    private lateinit var newPasswordInput: TextInputEditText
    private lateinit var confirmPasswordInput: TextInputEditText
    private lateinit var resetButton: MaterialButton

    private var currentEmail: String = ""

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_forgot_password)

        initViews()
        setupListeners()
    }

    private fun initViews() {
        emailInput = findViewById(R.id.emailInput)
        emailLayout = findViewById(R.id.emailLayout)
        submitButton = findViewById(R.id.submitButton)
        progressIndicator = findViewById(R.id.progressIndicator)
        backButton = findViewById(R.id.backButton)

        // OTP stage views
        otpLayout = findViewById(R.id.otpLayout)
        otpInput = findViewById(R.id.otpInput)
        newPasswordInput = findViewById(R.id.newPasswordInput)
        confirmPasswordInput = findViewById(R.id.confirmPasswordInput)
        resetButton = findViewById(R.id.resetButton)
    }

    private fun setupListeners() {
        backButton.setOnClickListener {
            onBackPressedDispatcher.onBackPressed()
        }

        submitButton.setOnClickListener {
            val email = emailInput.text.toString().trim()
            if (validateEmail(email)) {
                requestPasswordReset(email)
            }
        }

        resetButton.setOnClickListener {
            val otp = otpInput.text.toString().trim()
            val newPassword = newPasswordInput.text.toString()
            val confirmPassword = confirmPasswordInput.text.toString()

            if (validateResetInputs(otp, newPassword, confirmPassword)) {
                resetPassword(otp, newPassword)
            }
        }
    }

    private fun validateEmail(email: String): Boolean {
        return when {
            email.isEmpty() -> {
                emailLayout.error = "Email is required"
                false
            }
            !android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches() -> {
                emailLayout.error = "Please enter a valid email"
                false
            }
            else -> {
                emailLayout.error = null
                true
            }
        }
    }

    private fun validateResetInputs(otp: String, newPassword: String, confirmPassword: String): Boolean {
        var isValid = true

        if (otp.isEmpty() || otp.length < 4) {
            Toast.makeText(this, "Please enter a valid OTP", Toast.LENGTH_SHORT).show()
            isValid = false
        }

        if (newPassword.length < 8) {
            Toast.makeText(this, "Password must be at least 8 characters", Toast.LENGTH_SHORT).show()
            isValid = false
        }

        if (newPassword != confirmPassword) {
            Toast.makeText(this, "Passwords do not match", Toast.LENGTH_SHORT).show()
            isValid = false
        }

        // Check password complexity
        val hasUppercase = newPassword.any { it.isUpperCase() }
        val hasLowercase = newPassword.any { it.isLowerCase() }
        val hasDigit = newPassword.any { it.isDigit() }
        val hasSpecial = newPassword.any { !it.isLetterOrDigit() }

        if (!hasUppercase || !hasLowercase || !hasDigit || !hasSpecial) {
            Toast.makeText(
                this,
                "Password must contain uppercase, lowercase, number, and special character",
                Toast.LENGTH_LONG
            ).show()
            isValid = false
        }

        return isValid
    }

    private fun requestPasswordReset(email: String) {
        currentEmail = email
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(this@ForgotPasswordActivity)
                    .sendResetCode(mapOf("email" to email))

                if (response.isSuccessful && response.body()?.success == true) {
                    showOtpStage()
                    Toast.makeText(
                        this@ForgotPasswordActivity,
                        "Reset code sent to your email",
                        Toast.LENGTH_LONG
                    ).show()
                } else {
                    val errorMessage = response.body()?.message ?: "Failed to send reset code"
                    Toast.makeText(this@ForgotPasswordActivity, errorMessage, Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(
                    this@ForgotPasswordActivity,
                    "Network error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun resetPassword(otp: String, newPassword: String) {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val verifyResponse = RetrofitClient.instance(this@ForgotPasswordActivity)
                    .verifyResetCode(
                        mapOf(
                            "email" to currentEmail,
                            "verification_code" to otp
                        )
                    )

                if (!verifyResponse.isSuccessful || verifyResponse.body()?.success != true) {
                    val errorMessage = verifyResponse.body()?.message ?: "Failed to verify OTP"
                    Toast.makeText(this@ForgotPasswordActivity, errorMessage, Toast.LENGTH_SHORT).show()
                    return@launch
                }

                val resetResponse = RetrofitClient.instance(this@ForgotPasswordActivity)
                    .resetPassword(
                        mapOf(
                            "email" to currentEmail,
                            "password" to newPassword,
                            "password_confirmation" to newPassword
                        )
                    )

                if (resetResponse.isSuccessful && resetResponse.body()?.success == true) {
                    Toast.makeText(
                        this@ForgotPasswordActivity,
                        "Password reset successfully!",
                        Toast.LENGTH_LONG
                    ).show()
                    finish()
                } else {
                    val errorMessage = resetResponse.body()?.message ?: "Failed to reset password"
                    Toast.makeText(this@ForgotPasswordActivity, errorMessage, Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(
                    this@ForgotPasswordActivity,
                    "Network error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun showOtpStage() {
        emailLayout.visibility = View.GONE
        submitButton.visibility = View.GONE
        otpLayout.visibility = View.VISIBLE
    }

    private fun showLoading(show: Boolean) {
        progressIndicator.visibility = if (show) View.VISIBLE else View.GONE
        submitButton.isEnabled = !show
        resetButton.isEnabled = !show
    }

    fun resendOtp(view: View) {
        if (currentEmail.isNotEmpty()) {
            requestPasswordReset(currentEmail)
        }
    }
}
