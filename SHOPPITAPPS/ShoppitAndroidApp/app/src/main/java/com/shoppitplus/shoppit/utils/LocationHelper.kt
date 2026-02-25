package com.shoppitplus.shoppit.utils

import android.content.Context
import android.content.pm.PackageManager
import android.location.Location
import android.location.LocationManager
import android.os.Build
import androidx.core.content.ContextCompat

/**
 * Helper to obtain last known or current location for delivery zone checks.
 * Requires ACCESS_FINE_LOCATION permission to be granted.
 */
object LocationHelper {

    fun getLastLocation(context: Context): Location? {
        if (ContextCompat.checkSelfPermission(context, android.Manifest.permission.ACCESS_FINE_LOCATION)
            != PackageManager.PERMISSION_GRANTED
        ) {
            return null
        }
        val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return null
        val gps = lm.getLastKnownLocation(LocationManager.GPS_PROVIDER)
        val network = lm.getLastKnownLocation(LocationManager.NETWORK_PROVIDER)
        if (gps != null && network != null) {
            return if (gps.accuracy <= network.accuracy) gps else network
        }
        return gps ?: network
    }

    fun hasLocationPermission(context: Context): Boolean {
        return ContextCompat.checkSelfPermission(
            context,
            android.Manifest.permission.ACCESS_FINE_LOCATION
        ) == PackageManager.PERMISSION_GRANTED
    }

    fun isLocationEnabled(context: Context): Boolean {
        val lm = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return false
        return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            lm.isLocationEnabled
        } else {
            @Suppress("DEPRECATION")
            lm.isProviderEnabled(LocationManager.GPS_PROVIDER) ||
                lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
        }
    }
}
