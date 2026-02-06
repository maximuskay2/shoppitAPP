package com.shoppitplus.shoppit.models

import android.content.Context
import android.util.Log
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object RetrofitClient {
    private const val BASE_URL = " https://shopittplus.espays.org/api/v1/"
    private const val TAG = "RetrofitClient"

    // Logging interceptor for debugging
    private val logging = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }

    // Singleton Retrofit instance
    private var retrofit: Retrofit? = null

    // Thread-safe method to get Retrofit instance
    fun instance(context: Context): Api {
        if (retrofit == null) {
            synchronized(this) {
                if (retrofit == null) {
                    retrofit = Retrofit.Builder()
                        .baseUrl(BASE_URL)
                        .addConverterFactory(GsonConverterFactory.create())
                        .client(getOkHttpClient(context))
                        .build()
                }
            }
        }
        return retrofit!!.create(Api::class.java)
    }

    // OkHttpClient with interceptors and timeouts
    private fun getOkHttpClient(context: Context): OkHttpClient {
        return OkHttpClient.Builder()
            .addInterceptor(logging)
            .addInterceptor(getAuthInterceptor(context))
            .connectTimeout(120, TimeUnit.SECONDS) // Increase connection timeout
            .readTimeout(120, TimeUnit.SECONDS)    // Increase read timeout
            .writeTimeout(60, TimeUnit.SECONDS)   // Increase write timeout
            .build()

    }

    // Interceptor to handle authentication
    private fun getAuthInterceptor(context: Context): Interceptor {
        return Interceptor { chain ->
            val request = chain.request()
            val authToken = getAuthToken(context)

            // Get the current Content-Type from the request if it exists
            val contentType = request.header("Content-Type")

            val modifiedRequest = if (authToken.isNotEmpty()) {
                Log.d("RetrofitClient", "Request URL: ${request.url}")

                // Start with the request builder
                val requestBuilder = request.newBuilder()
                    .addHeader("Authorization", "Bearer $authToken")

                // Only add the Content-Type/Accept headers if they're not already set
                // This avoids overriding Content-Type set by @FormUrlEncoded or @Multipart
                if (contentType == null) {
                    requestBuilder.addHeader("Accept", "application/json")
                    // Don't force JSON for all requests - let Retrofit handle it based on annotations
                    // requestBuilder.addHeader("Content-Type", "application/json")
                }

                requestBuilder.build()
            } else {
                Log.w("RetrofitClient", "No auth token found!")
                request
            }

            chain.proceed(modifiedRequest)
        }
    }


    // Helper function to get auth token from SharedPreferences
    private fun getAuthToken(context: Context): String {
        val sharedPreferences = context.getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
        val token = sharedPreferences.getString("auth_token", "") ?: ""

        Log.d("AuthInterceptor", "Retrieved Token: $token") // Add this for debugging
        return token
    }


    // Helper function to get refresh token from SharedPreferences
    private fun getRefreshToken(context: Context): String {
        val sharedPreferences = context.getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
        return sharedPreferences.getString("refresh_token", "") ?: ""
    }

    // Helper function to save tokens in SharedPreferences
    private fun saveTokens(context: Context, authToken: String, refreshToken: String) {
        val sharedPreferences = context.getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
        sharedPreferences.edit().apply {
            putString("auth_token", authToken)
            putString("refresh_token", refreshToken)
            apply()
        }
    }

    // Handle logout by clearing tokens and redirecting to login screen
    private fun handleLogout(context: Context) {
        val sharedPreferences = context.getSharedPreferences("AppPrefs", Context.MODE_PRIVATE)
        sharedPreferences.edit().apply {
            remove("auth_token")
            remove("refresh_token")
            apply()
        }

        Log.d(TAG, "User logged out. Redirecting to login screen.")

        // Optionally navigate to login screen
        // val intent = Intent(context, LoginActivity::class.java)
        // intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        // context.startActivity(intent)
    }
}