// File: com/shoppitplus/shoppit/auth/AuthValidator.kt

package com.shoppitplus.shoppit.auth

import android.content.Context
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext
import kotlinx.coroutines.withTimeoutOrNull
import java.util.concurrent.TimeUnit

object AuthValidator {

    suspend fun isValidSession(context: Context): Boolean = withContext(Dispatchers.IO) {
        if (!AppPrefs.isLoggedIn(context)) return@withContext false

        val token = AppPrefs.getAuthToken(context) ?: return@withContext false

        // For now we only do basic local check
        // Remove/comment the server part until you implement the endpoint
        /*
        try {
            val response = withTimeoutOrNull(4000) {
                RetrofitClient.instance(context).validateToken()
            }
            return@withContext response?.success == true
        } catch (e: Exception) {
            return@withContext false
        }
        */

        // At minimum: token exists and is not empty
        return@withContext token.isNotBlank()
    }
}