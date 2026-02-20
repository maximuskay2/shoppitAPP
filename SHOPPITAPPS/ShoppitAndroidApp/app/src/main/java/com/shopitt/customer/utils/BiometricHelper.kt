package com.shopitt.customer.utils

import android.content.Context
import android.os.Build
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import androidx.biometric.BiometricManager
import androidx.biometric.BiometricPrompt
import androidx.core.content.ContextCompat
import androidx.fragment.app.FragmentActivity
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey

class BiometricHelper(private val context: Context) {

    companion object {
        private const val KEY_NAME = "shopitt_biometric_key"
        private const val ANDROID_KEYSTORE = "AndroidKeyStore"
        private const val PREFS_NAME = "biometric_prefs"
        private const val KEY_BIOMETRIC_ENABLED = "biometric_enabled"
        private const val KEY_ENCRYPTED_TOKEN = "encrypted_token"
    }

    interface BiometricCallback {
        fun onAuthenticationSuccess(token: String?)
        fun onAuthenticationError(errorCode: Int, errorMessage: String)
        fun onAuthenticationFailed()
    }

    private val prefs = context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)

    /**
     * Check if biometric authentication is available on this device
     */
    fun isBiometricAvailable(): Boolean {
        val biometricManager = BiometricManager.from(context)
        return when (biometricManager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG)) {
            BiometricManager.BIOMETRIC_SUCCESS -> true
            BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE -> false
            BiometricManager.BIOMETRIC_ERROR_HW_UNAVAILABLE -> false
            BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED -> false
            else -> false
        }
    }

    /**
     * Check if user has enabled biometric login
     */
    fun isBiometricEnabled(): Boolean {
        return prefs.getBoolean(KEY_BIOMETRIC_ENABLED, false) && isBiometricAvailable()
    }

    /**
     * Enable biometric authentication and store the encrypted token
     */
    fun enableBiometric(token: String) {
        try {
            generateSecretKey()
            val encryptedToken = encryptToken(token)
            prefs.edit()
                .putBoolean(KEY_BIOMETRIC_ENABLED, true)
                .putString(KEY_ENCRYPTED_TOKEN, encryptedToken)
                .apply()
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    /**
     * Disable biometric authentication
     */
    fun disableBiometric() {
        prefs.edit()
            .putBoolean(KEY_BIOMETRIC_ENABLED, false)
            .remove(KEY_ENCRYPTED_TOKEN)
            .apply()
        deleteSecretKey()
    }

    /**
     * Show biometric prompt for authentication
     */
    fun authenticate(
        activity: FragmentActivity,
        callback: BiometricCallback
    ) {
        val executor = ContextCompat.getMainExecutor(context)

        val biometricPrompt = BiometricPrompt(activity, executor,
            object : BiometricPrompt.AuthenticationCallback() {
                override fun onAuthenticationSucceeded(result: BiometricPrompt.AuthenticationResult) {
                    super.onAuthenticationSucceeded(result)
                    try {
                        val token = getStoredToken()
                        callback.onAuthenticationSuccess(token)
                    } catch (e: Exception) {
                        callback.onAuthenticationError(-1, "Failed to retrieve credentials")
                    }
                }

                override fun onAuthenticationError(errorCode: Int, errString: CharSequence) {
                    super.onAuthenticationError(errorCode, errString)
                    callback.onAuthenticationError(errorCode, errString.toString())
                }

                override fun onAuthenticationFailed() {
                    super.onAuthenticationFailed()
                    callback.onAuthenticationFailed()
                }
            })

        val promptInfo = BiometricPrompt.PromptInfo.Builder()
            .setTitle("Biometric Login")
            .setSubtitle("Use your fingerprint or face to login")
            .setNegativeButtonText("Use Password")
            .setConfirmationRequired(false)
            .build()

        biometricPrompt.authenticate(promptInfo)
    }

    /**
     * Get the stored authentication token
     */
    private fun getStoredToken(): String? {
        val encryptedToken = prefs.getString(KEY_ENCRYPTED_TOKEN, null)
        return encryptedToken?.let { decryptToken(it) }
    }

    /**
     * Generate a secret key for encryption
     */
    private fun generateSecretKey() {
        val keyGenerator = KeyGenerator.getInstance(
            KeyProperties.KEY_ALGORITHM_AES,
            ANDROID_KEYSTORE
        )

        val keyGenParameterSpec = KeyGenParameterSpec.Builder(
            KEY_NAME,
            KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
        )
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setUserAuthenticationRequired(false)
            .build()

        keyGenerator.init(keyGenParameterSpec)
        keyGenerator.generateKey()
    }

    /**
     * Get the secret key from keystore
     */
    private fun getSecretKey(): SecretKey? {
        val keyStore = KeyStore.getInstance(ANDROID_KEYSTORE)
        keyStore.load(null)
        return keyStore.getKey(KEY_NAME, null) as? SecretKey
    }

    /**
     * Delete the secret key from keystore
     */
    private fun deleteSecretKey() {
        try {
            val keyStore = KeyStore.getInstance(ANDROID_KEYSTORE)
            keyStore.load(null)
            keyStore.deleteEntry(KEY_NAME)
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    /**
     * Encrypt the authentication token
     */
    private fun encryptToken(token: String): String {
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.ENCRYPT_MODE, getSecretKey())

        val iv = cipher.iv
        val encryptedBytes = cipher.doFinal(token.toByteArray(Charsets.UTF_8))

        // Combine IV and encrypted data
        val combined = ByteArray(iv.size + encryptedBytes.size)
        System.arraycopy(iv, 0, combined, 0, iv.size)
        System.arraycopy(encryptedBytes, 0, combined, iv.size, encryptedBytes.size)

        return android.util.Base64.encodeToString(combined, android.util.Base64.DEFAULT)
    }

    /**
     * Decrypt the authentication token
     */
    private fun decryptToken(encryptedToken: String): String {
        val combined = android.util.Base64.decode(encryptedToken, android.util.Base64.DEFAULT)

        // Extract IV (first 12 bytes for GCM)
        val iv = combined.copyOfRange(0, 12)
        val encryptedBytes = combined.copyOfRange(12, combined.size)

        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        val spec = javax.crypto.spec.GCMParameterSpec(128, iv)
        cipher.init(Cipher.DECRYPT_MODE, getSecretKey(), spec)

        val decryptedBytes = cipher.doFinal(encryptedBytes)
        return String(decryptedBytes, Charsets.UTF_8)
    }

    /**
     * Get detailed biometric status message
     */
    fun getBiometricStatusMessage(): String {
        val biometricManager = BiometricManager.from(context)
        return when (biometricManager.canAuthenticate(BiometricManager.Authenticators.BIOMETRIC_STRONG)) {
            BiometricManager.BIOMETRIC_SUCCESS -> "Biometric authentication is available"
            BiometricManager.BIOMETRIC_ERROR_NO_HARDWARE -> "No biometric hardware found"
            BiometricManager.BIOMETRIC_ERROR_HW_UNAVAILABLE -> "Biometric hardware is unavailable"
            BiometricManager.BIOMETRIC_ERROR_NONE_ENROLLED -> "No biometric credentials enrolled. Please set up fingerprint or face recognition in device settings."
            BiometricManager.BIOMETRIC_ERROR_SECURITY_UPDATE_REQUIRED -> "Security update required"
            BiometricManager.BIOMETRIC_ERROR_UNSUPPORTED -> "Biometric authentication is not supported"
            BiometricManager.BIOMETRIC_STATUS_UNKNOWN -> "Biometric status unknown"
            else -> "Biometric authentication unavailable"
        }
    }
}
