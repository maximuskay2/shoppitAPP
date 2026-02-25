package com.shoppitplus.shoppit.auth

import android.content.Context
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull

object AuthValidator {
    private val apiClient = ShoppitApiClient()

    suspend fun isValidSession(context: Context): Boolean = withContext(Dispatchers.IO) {
        if (!AppPrefs.isLoggedIn(context)) return@withContext false

        val token = AppPrefs.getAuthToken(context) ?: return@withContext false

        if (token.isBlank()) return@withContext false

        // Basic check is done, now we can optionally do a server check using KMP client
        /*
        try {
            val response = withTimeoutOrNull(4000) {
                apiClient.validateToken(token)
            }
            return@withContext response?.success == true
        } catch (e: Exception) {
            return@withContext false
        }
        */

        return@withContext true
    }
}
