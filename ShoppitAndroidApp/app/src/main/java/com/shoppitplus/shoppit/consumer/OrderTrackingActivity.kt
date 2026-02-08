package com.shoppitplus.shoppit.consumer

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.util.Log
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.databinding.ActivityOrderTrackingBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import kotlinx.coroutines.launch

class OrderTrackingActivity : AppCompatActivity() {
    private lateinit var binding: ActivityOrderTrackingBinding
    private var orderId: String = ""
    private val tag = "OrderTrackingActivity"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityOrderTrackingBinding.inflate(layoutInflater)
        setContentView(binding.root)

        orderId = intent.getStringExtra("order_id") ?: ""
        binding.btnBack.setOnClickListener { finish() }
        binding.btnRefresh.setOnClickListener { loadTracking() }
        binding.btnOpenMaps.setOnClickListener { openDeliveryInMaps() }

        loadTracking()
    }

    private fun loadTracking() {
        if (orderId.isEmpty()) return
        Log.d(tag, "Loading tracking for order $orderId")
        binding.txtStatus.text = "Loading..."
        binding.txtEta.text = "-"
        binding.txtDriverLocation.text = "-"
        binding.txtDeliveryLocation.text = "-"
        binding.txtUpdatedAt.text = "-"

        lifecycleScope.launch {
            try {
                val api = RetrofitClient.instance(this@OrderTrackingActivity)
                val trackingResponse = api.getOrderTracking(orderId)
                val etaResponse = api.getOrderEta(orderId)

                if (trackingResponse.isSuccessful) {
                    val data = trackingResponse.body()?.data
                    Log.d(tag, "Tracking loaded: ${trackingResponse.body()?.message}")
                    binding.txtStatus.text = data?.status ?: "Unknown"
                    val avatarUrl = data?.driver?.avatar
                    if (!avatarUrl.isNullOrEmpty()) {
                        Glide.with(this@OrderTrackingActivity)
                            .load(avatarUrl)
                            .placeholder(android.R.drawable.sym_def_app_icon)
                            .into(binding.imgDriverAvatar)
                    } else {
                        binding.imgDriverAvatar.setImageResource(android.R.drawable.sym_def_app_icon)
                    }
                    val driver = data?.driver_location
                    binding.txtDriverLocation.text =
                        if (driver?.lat != null && driver.lng != null) {
                            "${driver.lat}, ${driver.lng}"
                        } else {
                            "Unavailable"
                        }
                    val delivery = data?.delivery_location
                    binding.txtDeliveryLocation.text =
                        if (delivery?.lat != null && delivery.lng != null) {
                            "${delivery.lat}, ${delivery.lng}"
                        } else {
                            "Unavailable"
                        }
                    binding.txtUpdatedAt.text = data?.updated_at ?: "-"
                } else {
                    binding.txtStatus.text = "Failed to load tracking"
                }

                if (etaResponse.isSuccessful) {
                    val eta = etaResponse.body()?.data?.eta_minutes
                    binding.txtEta.text = eta?.let { "$it min" } ?: "-"
                }
            } catch (e: Exception) {
                Log.w(tag, "Tracking load failed: ${e.message}")
                binding.txtStatus.text = "Tracking unavailable"
            }
        }
    }

    private fun openDeliveryInMaps() {
        val delivery = binding.txtDeliveryLocation.text.toString()
        if (delivery == "-" || delivery == "Unavailable") return
        Log.d(tag, "Opening maps for delivery: $delivery")
        val uri = Uri.parse("geo:0,0?q=$delivery")
        val intent = Intent(Intent.ACTION_VIEW, uri)
        intent.setPackage("com.google.android.apps.maps")
        startActivity(intent)
    }
}
