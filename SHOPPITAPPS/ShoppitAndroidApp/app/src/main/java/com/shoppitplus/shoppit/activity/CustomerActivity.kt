package com.shoppitplus.shoppit.activity

import android.os.Bundle
import android.view.View
import android.widget.TextView
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.navigation.findNavController
import com.shoppitplus.shoppit.R
import androidx.navigation.ui.setupWithNavController
import com.google.android.material.bottomnavigation.BottomNavigationView
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.CartManager
import kotlinx.coroutines.launch

class CustomerActivity : AppCompatActivity() {
    private lateinit var badge: TextView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        enableEdgeToEdge()
        setContentView(R.layout.activity_customer)
        val navController = findNavController(R.id.fragment)
        val bottomNavigationView = findViewById<BottomNavigationView>(R.id.bottomNavigation)

        // Link bottom navigation with navigation graph
        bottomNavigationView.setupWithNavController(navController)
        navController.addOnDestinationChangedListener { _, destination, _ ->
            when (destination.id) {
                R.id.checkoutFragment,
                R.id.fragment_paystack_webview,
                R.id.fragment_gift_form,
                R.id.fragment_order_note,
                R.id.editAddress,
                R.id.editProfile,
                R.id.wallet,
                    -> {
                    bottomNavigationView.visibility = View.GONE
                }

                else -> {
                    bottomNavigationView.visibility = View.VISIBLE
                }
            }
        }
        badge = findViewById(R.id.cartBadge)

        // Listen for cart changes
        CartManager.addListener { updateBadge() }

        // Initial load
        loadCartCount()

    }
    private fun loadCartCount() {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(this@CustomerActivity).getCart()
                if (response.success && response.data != null) {
                    val totalItems = response.data.vendors.sumOf { it.itemCount }
                    CartManager.setItemCount(totalItems)
                }
            } catch (e: Exception) {
                // Silent
            }
        }
    }

    private fun updateBadge() {
        val count = CartManager.getItemCount()
        if (count > 0) {
            badge.visibility = View.VISIBLE
            badge.text = if (count > 99) "99+" else count.toString()
        } else {
            badge.visibility = View.GONE
        }
    }

    override fun onDestroy() {
        CartManager.removeListener { updateBadge() }
        super.onDestroy()
    }
}