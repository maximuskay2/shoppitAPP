package com.shoppitplus.shoppit.vendor

import android.content.Intent
import android.graphics.Bitmap
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.PorterDuff
import android.os.Bundle
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ScrollView
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.bumptech.glide.Glide
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentOrderDetailsBinding
import com.shoppitplus.shoppit.databinding.LayoutStatusUpdateBinding // ← Make sure this exists
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.utils.GenericResponse
import com.shoppitplus.shoppit.utils.OrderDetail
import com.shoppitplus.shoppit.utils.OrderResponse
import retrofit2.Call
import retrofit2.Callback
import retrofit2.Response
import java.io.File
import java.io.FileOutputStream
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone

class OrderDetails : Fragment() {

    private var _binding: FragmentOrderDetailsBinding? = null
    private val binding get() = _binding!!

    private var orderId: String? = null
    private var currentOrder: OrderDetail? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        orderId = arguments?.getString("order_id")
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentOrderDetailsBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupToolbar()
        setupClicks()
        if (orderId == null) {
            Toast.makeText(requireContext(), "Invalid order", Toast.LENGTH_SHORT).show()
            findNavController().popBackStack()
            return
        }

        fetchOrderDetails(orderId!!)
    }

    private fun setupToolbar() {
        binding.toolbar.setNavigationOnClickListener {
            findNavController().popBackStack()
        }
    }

    private fun setupClicks() {
        binding.tvUpdateStatus.setOnClickListener {
            showStatusUpdateBottomSheet()
        }

        binding.btnShareReceipt.setOnClickListener {
            shareReceiptAsImage()
        }
    }

    private fun fetchOrderDetails(orderId: String) {
        showLoading(true)

        RetrofitClient.instance(requireContext())
            .getOrderDetails(orderId)
            .enqueue(object : Callback<OrderResponse> {
                override fun onResponse(
                    call: Call<OrderResponse>,
                    response: Response<OrderResponse>
                ) {
                    showLoading(false)

                    if (response.isSuccessful && response.body()?.success == true) {
                        val orderDetail = response.body()!!.data
                        currentOrder = orderDetail
                        bindOrderData(orderDetail)
                    } else {
                        val msg = response.body()?.message ?: "Failed to load order details"
                        Toast.makeText(requireContext(), msg, Toast.LENGTH_LONG).show()
                        parentFragmentManager.popBackStack()
                    }
                }

                override fun onFailure(call: Call<OrderResponse>, t: Throwable) {
                    showLoading(false)
                    Toast.makeText(
                        requireContext(),
                        "Network error: ${t.message}",
                        Toast.LENGTH_SHORT
                    ).show()
                    Log.e("OrderDetails", "Fetch failed", t)
                }
            })
    }

    private fun bindOrderData(order: OrderDetail) {
        with(binding) {
            toolbar.title = "Order ID: ${order.tracking_id}"
            tvOrderDate.text = formatDate(order.created_at)

            val item = order.line_items.firstOrNull()
            tvProductName.text = item?.product?.name ?: "Unknown Product"
            tvProductQuantity.text =
                "${item?.quantity ?: 1} x ₦${String.format("%,d", item?.price ?: 0)}"

            Glide.with(requireContext())
                .load(item?.product?.avatar?.firstOrNull())
                .placeholder(R.drawable.filter_chip_bg)
                .into(ivProduct)

            val subtotal = order.gross_total_amount - order.delivery_fee
            tvSubtotal.text = "₦${String.format("%,d", subtotal)}"
            tvDeliveryFee.text = "₦${String.format("%,d", order.delivery_fee)}"
            tvTotal.text = "₦${String.format("%,d", order.gross_total_amount)}"

            tvCustomerName.text = order.user.name
            tvCustomerAddress.text = order.user.address ?: "Not provided"
            tvCustomerPhone.text = order.user.phone ?: "Not provided"

            if (order.is_gift) {
                layoutGift.visibility = View.VISIBLE
                tvReceiverName.text = order.receiver_name ?: "N/A"
                tvReceiverAddress.text =
                    order.receiver_delivery_address ?: order.user.address ?: "N/A"
                tvReceiverPhone.text = order.receiver_phone ?: "N/A"
            } else {
                layoutGift.visibility = View.GONE
            }

            val statusText = order.status.replaceFirstChar { it.uppercase() }
            tvStatus.text = statusText

            val (bgColor, textColor) = when (order.status.uppercase()) {
                "COMPLETED" -> "#E8F5E9" to "#4CAF50"
                "DISPATCHED" -> "#E3F2FD" to "#2196F3"
                "CANCELLED" -> "#FFEBEE" to "#F44336"
                else -> "#FFF3E0" to "#FF9800"
            }

            tvStatus.setBackgroundColor(Color.parseColor(bgColor))
            tvStatus.setTextColor(Color.parseColor(textColor))
        }
    }

    private fun showStatusUpdateBottomSheet() {
        val sheet = BottomSheetDialog(requireContext())
        val sheetBinding = LayoutStatusUpdateBinding.inflate(layoutInflater)

        // Pre-check current status
        when (currentOrder?.status?.uppercase()) {
            "COMPLETED" -> sheetBinding.rbCompleted.isChecked = true
            "DISPATCHED" -> sheetBinding.rbDispatched.isChecked = true
            "CANCELLED" -> sheetBinding.rbCancelled.isChecked = true
        }

        sheetBinding.rgStatus.setOnCheckedChangeListener { _, checkedId ->
            val newStatus = when (checkedId) {
                R.id.rbCompleted -> "COMPLETED"
                R.id.rbDispatched -> "DISPATCHED"
                R.id.rbCancelled -> "CANCELLED"
                else -> return@setOnCheckedChangeListener
            }

            updateOrderStatus(newStatus)
            sheet.dismiss()
        }

        sheet.setContentView(sheetBinding.root)
        sheet.show()
    }

    private fun updateOrderStatus(newStatus: String) {
        showLoading(true)

        orderId?.let { id ->
            RetrofitClient.instance(requireContext())
                .updateOrderStatus(id, newStatus)
                .enqueue(object : Callback<GenericResponse> {
                    override fun onResponse(
                        call: Call<GenericResponse>,
                        response: Response<GenericResponse>
                    ) {
                        showLoading(false)
                        if (response.isSuccessful && response.body()?.success == true) {
                            Toast.makeText(requireContext(), "Status updated!", Toast.LENGTH_SHORT)
                                .show()
                            currentOrder?.status = newStatus
                            currentOrder?.let { bindOrderData(it) }
                        } else {
                            Toast.makeText(requireContext(), "Update failed", Toast.LENGTH_SHORT)
                                .show()
                        }
                    }

                    override fun onFailure(call: Call<GenericResponse>, t: Throwable) {
                        showLoading(false)
                        Toast.makeText(requireContext(), "Network error", Toast.LENGTH_SHORT).show()
                    }
                })
        }
    }

    private fun shareReceiptAsImage() {
        currentOrder?.let { order ->
            // Hide buttons/actions that shouldn't appear in receipt
            binding.btnShareReceipt.visibility = View.GONE
            binding.tvUpdateStatus.visibility = View.GONE

            // Take screenshot of the ScrollView content
            val bitmap = getScrollViewBitmap(binding.scrollView)

            // Restore visibility
            binding.btnShareReceipt.visibility = View.VISIBLE
            binding.tvUpdateStatus.visibility = View.VISIBLE

            if (bitmap != null) {
                shareBitmap(bitmap, "Receipt - ${order.tracking_id}.png")
            } else {
                Toast.makeText(
                    requireContext(),
                    "Failed to generate receipt image",
                    Toast.LENGTH_SHORT
                ).show()
            }
        } ?: run {
            Toast.makeText(requireContext(), "Order not loaded yet", Toast.LENGTH_SHORT).show()
        }
    }

    // Helper: Capture ScrollView as Bitmap
    private fun getScrollViewBitmap(scrollView: ScrollView): Bitmap? {
        var bitmap: Bitmap? = null
        try {
            val totalHeight = scrollView.getChildAt(0).height
            val totalWidth = scrollView.getChildAt(0).width

            bitmap = Bitmap.createBitmap(totalWidth, totalHeight, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(bitmap)
            scrollView.draw(canvas)
        } catch (e: Exception) {
            Log.e("OrderDetails", "Error capturing receipt", e)
        }
        return bitmap
    }

    // Helper: Save bitmap temporarily and share
    private fun shareBitmap(bitmap: Bitmap, fileName: String) {
        try {
            val cachePath = File(requireContext().cacheDir, "images").apply { mkdirs() }
            val file = File(cachePath, fileName)

            FileOutputStream(file).use { out ->
                bitmap.compress(Bitmap.CompressFormat.PNG, 100, out)
            }

            // Correct authority using applicationId
            val contentUri = FileProvider.getUriForFile(
                requireContext(),
                "${requireContext().applicationInfo.packageName}.provider",  // Safe way
                file
            )

            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                type = "image/png"
                putExtra(Intent.EXTRA_STREAM, contentUri)
                putExtra(Intent.EXTRA_TEXT, "Order Receipt - ${currentOrder?.tracking_id}")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }

            startActivity(Intent.createChooser(shareIntent, "Share Receipt"))
        } catch (e: Exception) {
            Log.e("OrderDetails", "Share failed", e)
            Toast.makeText(requireContext(), "Failed to share receipt", Toast.LENGTH_SHORT).show()
        }
    }

    private fun showLoading(show: Boolean) {
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE

        if (show) {
            binding.progressBar.indeterminateDrawable.setColorFilter(
                ContextCompat.getColor(requireContext(), R.color.primary_color),
                PorterDuff.Mode.SRC_IN
            )
        }
    }

    private fun formatDate(dateString: String): String {
        return try {
            val input = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault())
            input.timeZone = TimeZone.getTimeZone("UTC")
            val date = input.parse(dateString.replace(Regex("\\.\\d+Z$"), ""))!!
            SimpleDateFormat("dd/MM/yyyy · hh:mm a", Locale.getDefault()).format(date)
        } catch (e: Exception) {
            dateString.take(16).replace("T", " · ")
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}