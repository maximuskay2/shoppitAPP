package com.shoppitplus.shoppit.models

import android.content.Context
import android.util.Log
import com.shoppitplus.shoppit.BuildConfig
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import okhttp3.Response
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import org.json.JSONObject
import java.io.IOException
import java.util.concurrent.TimeUnit

object RetrofitClient {
    private const val TAG = "RetrofitClient"
    private const val MAX_RETRY_ATTEMPTS = 2
    private const val RETRY_BACKOFF_MS = 500L
    private const val REFRESH_PATH = "auth/refresh"

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
                        .baseUrl(BuildConfig.BASE_URL)
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
            .addInterceptor(RetryInterceptor())
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

            val response = chain.proceed(modifiedRequest)

            if (response.code != 401) {
                return@Interceptor response
            }

            val refreshToken = getRefreshToken(context)
            if (refreshToken.isEmpty()) {
                handleLogout(context)
                return@Interceptor response
            }

            val newToken = refreshAuthToken(refreshToken)
            if (newToken.isEmpty()) {
                handleLogout(context)
                return@Interceptor response
            }

            saveTokens(context, newToken, refreshToken)

            val retryRequest = request.newBuilder()
                .addHeader("Authorization", "Bearer $newToken")
                .build()

            response.close()
            chain.proceed(retryRequest)
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

    private fun refreshAuthToken(refreshToken: String): String {
        return try {
            val baseUrl = BuildConfig.BASE_URL.trimEnd('/') + "/"
            val url = baseUrl + REFRESH_PATH
            val payload = JSONObject(mapOf("refresh_token" to refreshToken)).toString()
            val body = payload.toRequestBody("application/json".toMediaType())
            val request = Request.Builder()
                .url(url)
                .post(body)
                .build()

            val client = OkHttpClient.Builder()
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .build()

            val response = client.newCall(request).execute()
            if (!response.isSuccessful) {
                response.close()
                return ""
            }

            val responseBody = response.body?.string().orEmpty()
            response.close()
            val json = JSONObject(responseBody)
            val token = json.optJSONObject("data")?.optString("token") ?: ""
            token
        } catch (e: Exception) {
            Log.w(TAG, "Token refresh failed: ${e.message}")
            ""
        }
    }

    private class RetryInterceptor : Interceptor {
        override fun intercept(chain: Interceptor.Chain): Response {
            val request = chain.request()

            if (request.method != "GET" && request.method != "HEAD") {
                return chain.proceed(request)
            }

            var attempt = 0
            var lastException: IOException? = null
            var response: Response? = null

            while (attempt <= MAX_RETRY_ATTEMPTS) {
                try {
                    response = chain.proceed(request)
                    if (response.code != 429 && response.code !in 500..599) {
                        return response
                    }
                    if (attempt == MAX_RETRY_ATTEMPTS) {
                        return response
                    }
                    response.close()
                } catch (e: IOException) {
                    lastException = e
                    if (attempt == MAX_RETRY_ATTEMPTS) {
                        throw e
                    }
                }

                attempt += 1
                Thread.sleep(RETRY_BACKOFF_MS * attempt)
            }

            if (response != null) {
                return response
            }

            throw lastException ?: IOException("Retry failed without response")
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