package com.shopitt.vendor.ui.coupons

import android.content.DialogInterface
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.google.android.material.button.MaterialButton
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.floatingactionbutton.FloatingActionButton
import com.google.android.material.progressindicator.CircularProgressIndicator
import com.google.android.material.textfield.TextInputEditText
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.VendorCouponDto
import kotlinx.coroutines.launch
import java.util.*

data class Coupon(
    val uuid: String,
    val code: String,
    val description: String?,
    val discountType: String,
    val discountValue: Double,
    val minOrderAmount: Double?,
    val maxDiscount: Double?,
    val usageLimit: Int?,
    val usedCount: Int,
    val startDate: String?,
    val endDate: String?,
    val isActive: Boolean
)

class CouponManagementFragment : Fragment() {

    private lateinit var recyclerView: RecyclerView
    private lateinit var progressIndicator: CircularProgressIndicator
    private lateinit var emptyView: View
    private lateinit var fabAddCoupon: FloatingActionButton

    private val coupons = mutableListOf<Coupon>()
    private lateinit var adapter: CouponAdapter

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        return inflater.inflate(R.layout.fragment_coupon_management, container, false)
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        initViews(view)
        setupRecyclerView()
        setupListeners()
        loadCoupons()
    }

    private fun initViews(view: View) {
        recyclerView = view.findViewById(R.id.recyclerView)
        progressIndicator = view.findViewById(R.id.progressIndicator)
        emptyView = view.findViewById(R.id.emptyView)
        fabAddCoupon = view.findViewById(R.id.fabAddCoupon)
    }

    private fun setupRecyclerView() {
        adapter = CouponAdapter(
            coupons,
            onEditClick = { coupon -> showEditDialog(coupon) },
            onDeleteClick = { coupon -> confirmDelete(coupon) },
            onToggleStatus = { coupon -> toggleCouponStatus(coupon) }
        )
        recyclerView.layoutManager = LinearLayoutManager(requireContext())
        recyclerView.adapter = adapter
    }

    private fun setupListeners() {
        fabAddCoupon.setOnClickListener {
            showCreateDialog()
        }
    }

    private fun loadCoupons() {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getVendorCoupons()
                if (response.isSuccessful && response.body()?.success == true) {
                    coupons.clear()
                    response.body()?.data?.map { dto ->
                        Coupon(
                            uuid = dto.uuid,
                            code = dto.code,
                            description = dto.description,
                            discountType = dto.discountType,
                            discountValue = dto.discountValue,
                            minOrderAmount = dto.minOrderAmount,
                            maxDiscount = dto.maxDiscount,
                            usageLimit = dto.usageLimit,
                            usedCount = dto.usedCount,
                            startDate = dto.startDate,
                            endDate = dto.endDate,
                            isActive = dto.isActive
                        )
                    }?.let { coupons.addAll(it) }
                    adapter.notifyDataSetChanged()
                    updateEmptyState()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                showLoading(false)
            }
        }
    }

    private fun showCreateDialog() {
        val dialogView = layoutInflater.inflate(R.layout.dialog_coupon_form, null)

        val codeInput = dialogView.findViewById<TextInputEditText>(R.id.codeInput)
        val descriptionInput = dialogView.findViewById<TextInputEditText>(R.id.descriptionInput)
        val discountValueInput = dialogView.findViewById<TextInputEditText>(R.id.discountValueInput)
        val minOrderInput = dialogView.findViewById<TextInputEditText>(R.id.minOrderInput)
        val usageLimitInput = dialogView.findViewById<TextInputEditText>(R.id.usageLimitInput)

        // Generate random code
        dialogView.findViewById<MaterialButton>(R.id.generateCodeButton).setOnClickListener {
            codeInput.setText(generateCouponCode())
        }

        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Create Coupon")
            .setView(dialogView)
            .setPositiveButton("Create") { _: DialogInterface, _: Int ->
                val code = codeInput.text.toString().uppercase()
                val description = descriptionInput.text.toString()
                val discountValue = discountValueInput.text.toString().toDoubleOrNull() ?: 0.0
                val minOrder = minOrderInput.text.toString().toDoubleOrNull()
                val usageLimit = usageLimitInput.text.toString().toIntOrNull()

                if (code.isNotEmpty() && discountValue > 0) {
                    createCoupon(code, description, discountValue, minOrder, usageLimit)
                } else {
                    Toast.makeText(requireContext(), "Please fill required fields", Toast.LENGTH_SHORT).show()
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showEditDialog(coupon: Coupon) {
        val dialogView = layoutInflater.inflate(R.layout.dialog_coupon_form, null)

        val codeInput = dialogView.findViewById<TextInputEditText>(R.id.codeInput)
        val descriptionInput = dialogView.findViewById<TextInputEditText>(R.id.descriptionInput)
        val discountValueInput = dialogView.findViewById<TextInputEditText>(R.id.discountValueInput)
        val minOrderInput = dialogView.findViewById<TextInputEditText>(R.id.minOrderInput)
        val usageLimitInput = dialogView.findViewById<TextInputEditText>(R.id.usageLimitInput)

        // Pre-fill values
        codeInput.setText(coupon.code)
        codeInput.isEnabled = false // Can't change code
        descriptionInput.setText(coupon.description)
        discountValueInput.setText(coupon.discountValue.toString())
        minOrderInput.setText(coupon.minOrderAmount?.toString() ?: "")
        usageLimitInput.setText(coupon.usageLimit?.toString() ?: "")

        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Edit Coupon")
            .setView(dialogView)
            .setPositiveButton("Update") { _: DialogInterface, _: Int ->
                val description = descriptionInput.text.toString()
                val discountValue = discountValueInput.text.toString().toDoubleOrNull() ?: 0.0
                val minOrder = minOrderInput.text.toString().toDoubleOrNull()
                val usageLimit = usageLimitInput.text.toString().toIntOrNull()

                updateCoupon(coupon.uuid, description, discountValue, minOrder, usageLimit)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun confirmDelete(coupon: Coupon) {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Delete Coupon")
            .setMessage("Are you sure you want to delete the coupon '${coupon.code}'?")
            .setPositiveButton("Delete") { _: DialogInterface, _: Int ->
                deleteCoupon(coupon.uuid)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun createCoupon(
        code: String,
        description: String,
        discountValue: Double,
        minOrder: Double?,
        usageLimit: Int?
    ) {
        lifecycleScope.launch {
            try {
                val params = mutableMapOf<String, Any>(
                    "code" to code,
                    "discount_type" to "percentage",
                    "discount_value" to discountValue,
                    "is_active" to true
                )
                description.takeIf { it.isNotEmpty() }?.let { params["description"] = it }
                minOrder?.let { params["min_order_amount"] = it }
                usageLimit?.let { params["usage_limit"] = it }

                val response = RetrofitClient.instance(requireContext()).createVendorCoupon(params)
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Coupon created", Toast.LENGTH_SHORT).show()
                    loadCoupons()
                } else {
                    Toast.makeText(
                        requireContext(),
                        response.body()?.message ?: "Failed to create coupon",
                        Toast.LENGTH_SHORT
                    ).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun updateCoupon(
        uuid: String,
        description: String,
        discountValue: Double,
        minOrder: Double?,
        usageLimit: Int?
    ) {
        lifecycleScope.launch {
            try {
                val params = mutableMapOf<String, Any>(
                    "discount_value" to discountValue
                )
                description.takeIf { it.isNotEmpty() }?.let { params["description"] = it }
                minOrder?.let { params["min_order_amount"] = it }
                usageLimit?.let { params["usage_limit"] = it }

                val response = RetrofitClient.instance(requireContext()).updateVendorCoupon(uuid, params)
                if (response.isSuccessful && response.body()?.success == true) {
                    Toast.makeText(requireContext(), "Coupon updated", Toast.LENGTH_SHORT).show()
                    loadCoupons()
                } else {
                    Toast.makeText(requireContext(), "Failed to update coupon", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun deleteCoupon(uuid: String) {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).deleteVendorCoupon(uuid)
                if (response.isSuccessful) {
                    Toast.makeText(requireContext(), "Coupon deleted", Toast.LENGTH_SHORT).show()
                    loadCoupons()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun toggleCouponStatus(coupon: Coupon) {
        lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).updateVendorCoupon(
                    coupon.uuid,
                    mapOf("is_active" to !coupon.isActive)
                )
                if (response.isSuccessful) {
                    loadCoupons()
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun generateCouponCode(): String {
        val chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        return (1..8).map { chars.random() }.joinToString("")
    }

    private fun showLoading(show: Boolean) {
        progressIndicator.visibility = if (show) View.VISIBLE else View.GONE
    }

    private fun updateEmptyState() {
        emptyView.visibility = if (coupons.isEmpty()) View.VISIBLE else View.GONE
        recyclerView.visibility = if (coupons.isEmpty()) View.GONE else View.VISIBLE
    }
}

class CouponAdapter(
    private val coupons: List<Coupon>,
    private val onEditClick: (Coupon) -> Unit,
    private val onDeleteClick: (Coupon) -> Unit,
    private val onToggleStatus: (Coupon) -> Unit
) : RecyclerView.Adapter<CouponAdapter.CouponViewHolder>() {

    class CouponViewHolder(view: View) : RecyclerView.ViewHolder(view) {
        // Bind views here
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): CouponViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_coupon, parent, false)
        return CouponViewHolder(view)
    }

    override fun onBindViewHolder(holder: CouponViewHolder, position: Int) {
        val coupon = coupons[position]
        // Bind coupon data to views
    }

    override fun getItemCount() = coupons.size
}
