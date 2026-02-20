package com.shoppitplus.shoppit.ui

import android.content.Context

object AppPrefs {
    private const val PREFS_NAME = "AppPrefs"

    private const val KEY_IS_LOGGED_IN = "is_logged_in"
    private const val KEY_USER_TYPE = "user_role"           // "vendor" / "customer"
    private const val KEY_USER_ID = "user_id"
    private const val KEY_AUTH_TOKEN = "auth_token"

    fun saveLogin(
        context: Context,
        userType: String,
        userId: String? = null,
        token: String? = null
    ) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE).edit().apply {
            putBoolean(KEY_IS_LOGGED_IN, true)
            putString(KEY_USER_TYPE, userType.trim().lowercase())
            userId?.let { putString(KEY_USER_ID, it) }
            token?.let { putString(KEY_AUTH_TOKEN, it) }
            apply()
        }
    }

    fun clearLogin(context: Context) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE).edit().apply {
            // Only remove auth related keys - NEVER clear everything!
            remove(KEY_IS_LOGGED_IN)
            remove(KEY_USER_TYPE)
            remove(KEY_USER_ID)
            remove(KEY_AUTH_TOKEN)
            apply()
        }
    }

    fun isLoggedIn(context: Context): Boolean =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getBoolean(KEY_IS_LOGGED_IN, false)

    fun getUserType(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_USER_TYPE, null)

    fun getUserId(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_USER_ID, null)

    fun getAuthToken(context: Context): String? =
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_AUTH_TOKEN, null)
}