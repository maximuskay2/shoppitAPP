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
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.bumptech.glide.Glide
import com.google.android.material.bottomsheet.BottomSheetDialog
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentOrderDetailsBinding
import com.shoppitplus.shoppit.databinding.LayoutStatusUpdateBinding
import com.shoppitplus.shoppit.shared.models.OrderDetail
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import kotlinx.coroutines.launch
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
    private val apiClient = ShoppitApiClient()
    private var authToken: String? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        orderId = arguments?.getString("order_id")
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentOrderDetailsBinding.inflate(inflater, container, false)
        authToken = AppPrefs.getAuthToken(requireContext())
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupToolbar()
        setupClicks()
        if (orderId == null) {
            Toast.makeText(requireContext(), getString(R.string.snack_invalid_order), Toast.LENGTH_SHORT).show()
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

        binding.btnTrackOrder.setOnClickListener {
            val id = orderId ?: return@setOnClickListener
            val intent = Intent(requireContext(), VendorOrderTrackingActivity::class.java)
            intent.putExtra("order_id", id)
            startActivity(intent)
        }

        binding.btnShareReceipt.setOnClickListener {
            shareReceiptAsImage()
        }
    }

    private fun fetchOrderDetails(orderId: String) {
        showLoading(true)

        lifecycleScope.launch {
            try {
                val response = apiClient.getOrderDetails(authToken!!, orderId)
                showLoading(false)

                if (response.success) {
                    val orderDetail = response.data
                    currentOrder = orderDetail
                    bindOrderData(orderDetail)
                } else {
                    Toast.makeText(requireContext(), response.message, Toast.LENGTH_LONG).show()
                    parentFragmentManager.popBackStack()
                }
            } catch (e: Exception) {
                showLoading(false)
                Toast.makeText(requireContext(), getString(R.string.snack_network_error), Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun bindOrderData(order: OrderDetail) {
        with(binding) {
            toolbar.title = "Order ID: ${order.trackingId}"
            tvOrderDate.text = formatDate(order.createdAt)

            val item = order.lineItems.firstOrNull()
            tvProductName.text = item?.productName ?: item?.product?.name ?: "Unknown Product"
            tvProductQuantity.text =
                "${item?.quantity ?: 1} x ₦${String.format("%,d", item?.price?.toInt() ?: 0)}"

            Glide.with(requireContext())
                .load(item?.product?.avatar?.firstOrNull()?.secureUrl)
                .placeholder(R.drawable.filter_chip_bg)
                .into(ivProduct)

            val subtotal = order.grossTotalAmount - order.deliveryFee
            tvSubtotal.text = "₦${String.format("%,d", subtotal.toInt())}"
            tvDeliveryFee.text = "₦${String.format("%,d", order.deliveryFee.toInt())}"
            tvTotal.text = "₦${String.format("%,d", order.grossTotalAmount.toInt())}"

            tvCustomerName.text = order.user.name
            tvCustomerAddress.text = order.user.address ?: "Not provided"
            tvCustomerPhone.text = order.user.phone ?: "Not provided"

            if (order.isGift) {
                layoutGift.visibility = View.VISIBLE
                tvReceiverName.text = order.receiverName ?: "N/A"
                tvReceiverAddress.text =
                    order.receiverDeliveryAddress ?: order.user.address ?: "N/A"
                tvReceiverPhone.text = order.receiverPhone ?: "N/A"
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

        lifecycleScope.launch {
            try {
                val response = apiClient.updateOrderStatus(authToken!!, orderId!!, newStatus)
                showLoading(false)
                if (response.success) {
                    Toast.makeText(requireContext(), getString(R.string.snack_order_status_updated), Toast.LENGTH_SHORT).show()
                    currentOrder?.status = newStatus
                    currentOrder?.let { bindOrderData(it) }
                } else {
                    Toast.makeText(requireContext(), getString(R.string.snack_update_failed), Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                showLoading(false)
                Toast.makeText(requireContext(), getString(R.string.snack_network_error), Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun shareReceiptAsImage() {
        currentOrder?.let { order ->
            binding.btnShareReceipt.visibility = View.GONE
            binding.tvUpdateStatus.visibility = View.GONE

            val bitmap = getScrollViewBitmap(binding.scrollView)

            binding.btnShareReceipt.visibility = View.VISIBLE
            binding.tvUpdateStatus.visibility = View.VISIBLE

            if (bitmap != null) {
                shareBitmap(bitmap, "Receipt - ${order.trackingId}.png")
            } else {
                Toast.makeText(requireContext(), "Failed to generate receipt image", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun getScrollViewBitmap(scrollView: ScrollView): Bitmap? {
        return try {
            val totalHeight = scrollView.getChildAt(0).height
            val totalWidth = scrollView.getChildAt(0).width
            val bitmap = Bitmap.createBitmap(totalWidth, totalHeight, Bitmap.Config.ARGB_8888)
            val canvas = Canvas(bitmap)
            scrollView.draw(canvas)
            bitmap
        } catch (e: Exception) {
            null
        }
    }

    private fun shareBitmap(bitmap: Bitmap, fileName: String) {
        try {
            val cachePath = File(requireContext().cacheDir, "images").apply { mkdirs() }
            val file = File(cachePath, fileName)
            FileOutputStream(file).use { out -> bitmap.compress(Bitmap.CompressFormat.PNG, 100, out) }

            val contentUri = FileProvider.getUriForFile(requireContext(), "${requireContext().packageName}.provider", file)
            val shareIntent = Intent(Intent.ACTION_SEND).apply {
                type = "image/png"
                putExtra(Intent.EXTRA_STREAM, contentUri)
                putExtra(Intent.EXTRA_TEXT, "Order Receipt - ${currentOrder?.trackingId}")
                addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
            }
            startActivity(Intent.createChooser(shareIntent, "Share Receipt"))
        } catch (e: Exception) {
            Toast.makeText(requireContext(), "Failed to share receipt", Toast.LENGTH_SHORT).show()
        }
    }

    private fun showLoading(show: Boolean) {
        binding.loadingOverlay.visibility = if (show) View.VISIBLE else View.GONE
        binding.progressBar.visibility = if (show) View.VISIBLE else View.GONE
        if (show) {
            binding.progressBar.indeterminateDrawable.setColorFilter(ContextCompat.getColor(requireContext(), R.color.primary_color), PorterDuff.Mode.SRC_IN)
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
