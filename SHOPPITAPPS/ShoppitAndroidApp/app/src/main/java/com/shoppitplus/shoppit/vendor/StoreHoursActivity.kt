package com.shoppitplus.shoppit.vendor

import android.app.TimePickerDialog
import android.os.Bundle
import android.util.Log
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.shoppitplus.shoppit.databinding.ActivityStoreHoursBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import com.shoppitplus.shoppit.utils.StoreHoursRequest
import kotlinx.coroutines.launch
import java.util.Calendar

class StoreHoursActivity : AppCompatActivity() {
    private lateinit var binding: ActivityStoreHoursBinding
    private val tag = "StoreHoursActivity"

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityStoreHoursBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.btnBack.setOnClickListener { finish() }
        binding.inputOpening.setOnClickListener { pickTime(isOpening = true) }
        binding.inputClosing.setOnClickListener { pickTime(isOpening = false) }
        binding.btnSave.setOnClickListener { saveHours() }

        loadCurrentHours()
    }

    private fun loadCurrentHours() {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(this@StoreHoursActivity).getVendorDetails().execute()
                if (response.isSuccessful && response.body()?.success == true) {
                    val data = response.body()!!.data
                    binding.inputOpening.setText(data.opening_time)
                    binding.inputClosing.setText(data.closing_time)
                    Log.d(tag, "Loaded hours: ${data.opening_time}-${data.closing_time}")
                }
            } catch (_: Exception) {
                Log.w(tag, "Failed to load current hours")
            }
        }
    }

    private fun pickTime(isOpening: Boolean) {
        val calendar = Calendar.getInstance()
        val dialog = TimePickerDialog(
            this,
            { _, hourOfDay, minute ->
                val value = String.format("%02d:%02d", hourOfDay, minute)
                if (isOpening) {
                    binding.inputOpening.setText(value)
                } else {
                    binding.inputClosing.setText(value)
                }
            },
            calendar.get(Calendar.HOUR_OF_DAY),
            calendar.get(Calendar.MINUTE),
            true
        )
        dialog.show()
    }

    private fun saveHours() {
        val opening = binding.inputOpening.text.toString().trim()
        val closing = binding.inputClosing.text.toString().trim()
        if (opening.isEmpty() || closing.isEmpty()) {
            TopBanner.showError(this, "Please select both opening and closing times.")
            return
        }

        lifecycleScope.launch {
            try {
                Log.d(tag, "Updating hours: $opening-$closing")
                val response = RetrofitClient.instance(this@StoreHoursActivity)
                    .updateVendorStoreHours(StoreHoursRequest(opening, closing))
                if (response.success) {
                    TopBanner.showSuccess(this@StoreHoursActivity, "Store hours updated.")
                    finish()
                } else {
                    Log.w(tag, "Update failed: ${response.message}")
                    TopBanner.showError(this@StoreHoursActivity, response.message)
                }
            } catch (_: Exception) {
                Log.w(tag, "Update failed: network error")
                TopBanner.showError(this@StoreHoursActivity, "Network error.")
            }
        }
    }
}
