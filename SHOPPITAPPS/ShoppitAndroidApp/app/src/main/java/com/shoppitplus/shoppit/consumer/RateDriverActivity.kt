package com.shoppitplus.shoppit.consumer

import android.os.Bundle
import android.util.Log
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.databinding.ActivityRateDriverBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.ReviewRequest
import kotlinx.coroutines.launch

class RateDriverActivity : AppCompatActivity() {
    private lateinit var binding: ActivityRateDriverBinding
    private var orderId: String = ""
    private var driverId: String? = null
    private val tag = "RateDriverActivity"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityRateDriverBinding.inflate(layoutInflater)
        setContentView(binding.root)

        orderId = intent.getStringExtra("order_id") ?: ""

        binding.btnBack.setOnClickListener { finish() }
        binding.btnSubmit.setOnClickListener { submitReview() }

        loadDriverFromTracking()
    }

    private fun loadDriverFromTracking() {
        if (orderId.isEmpty()) return
        Log.d(tag, "Loading driver for order $orderId")
        binding.txtDriverStatus.text = "Loading driver..."
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(this@RateDriverActivity)
                    .getOrderTracking(orderId)
                if (response.isSuccessful) {
                    val data = response.body()?.data
                    driverId = data?.driver_id
                    val avatarUrl = data?.driver?.avatar
                    if (!avatarUrl.isNullOrEmpty()) {
                        Glide.with(this@RateDriverActivity)
                            .load(avatarUrl)
                            .placeholder(android.R.drawable.sym_def_app_icon)
                            .into(binding.imgDriverAvatar)
                    } else {
                        binding.imgDriverAvatar.setImageResource(android.R.drawable.sym_def_app_icon)
                    }
                    Log.d(tag, "Driver resolved: $driverId")
                    binding.txtDriverStatus.text =
                        if (driverId == null) "Driver not assigned yet" else "Driver ready for rating"
                } else {
                    binding.txtDriverStatus.text = "Unable to load driver"
                }
            } catch (e: Exception) {
                Log.w(tag, "Driver load failed: ${e.message}")
                binding.txtDriverStatus.text = "Unable to load driver"
            }
        }
    }

    private fun submitReview() {
        val rating = binding.ratingBar.rating.toInt()
        val comment = binding.inputComment.text.toString().trim().ifEmpty { null }
        val targetDriverId = driverId

        if (targetDriverId == null) {
            Log.w(tag, "Driver not assigned yet, cannot submit review.")
            TopBanner.showError(this, "Driver information is not available yet.")
            return
        }
        if (rating < 1) {
            TopBanner.showError(this, "Please select a rating.")
            return
        }

        lifecycleScope.launch {
            try {
                Log.d(tag, "Submitting review for driver $targetDriverId")
                val request = ReviewRequest(
                    rating = rating,
                    comment = comment,
                    reviewable_type = "driver",
                    reviewable_id = targetDriverId
                )
                val response = RetrofitClient.instance(this@RateDriverActivity)
                    .submitReview(request)
                if (response.isSuccessful && response.body()?.success == true) {
                    TopBanner.showSuccess(this@RateDriverActivity, "Review submitted.")
                    finish()
                } else {
                    Log.w(tag, "Review submit failed: ${response.body()?.message}")
                    TopBanner.showError(
                        this@RateDriverActivity,
                        response.body()?.message ?: "Failed to submit review."
                    )
                }
            } catch (e: Exception) {
                Log.w(tag, "Review submit failed: ${e.message}")
                TopBanner.showError(this@RateDriverActivity, "Network error.")
            }
        }
    }
}
