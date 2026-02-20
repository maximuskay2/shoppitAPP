package com.shoppitplus.shoppit.notification

import android.os.Bundle
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.NotificationAdapter
import com.shoppitplus.shoppit.databinding.FragmentNotificationBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import kotlinx.coroutines.launch

class Notification : Fragment() {

    private var _binding: FragmentNotificationBinding? = null
    private val binding get() = _binding!!

    private val adapter = NotificationAdapter { notification ->
        // Mark as read
        markAsRead(notification.id)

        // Navigate to order details if applicable
        notification.data.order_id?.let { orderId ->
            val bundle = Bundle().apply {
                putString("order_id", orderId)
            }
            findNavController().navigate(R.id.action_notification_to_orderDetails, bundle)
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentNotificationBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        setupToolbar()
        setupRecyclerView()
        setupSwipeRefresh()
        fetchNotifications()
    }

    private fun setupToolbar() {
        binding.toolbar.setNavigationOnClickListener {
            findNavController().popBackStack()
        }
    }

    private fun setupRecyclerView() {
        binding.rvNotifications.apply {
            layoutManager = LinearLayoutManager(requireContext())
            adapter = this@Notification.adapter
        }
    }

    private fun setupSwipeRefresh() {
        binding.swipeRefresh.setOnRefreshListener {
            fetchNotifications()
        }
        binding.swipeRefresh.setColorSchemeResources(R.color.primary_color)
    }

    private fun fetchNotifications() {
        binding.swipeRefresh.isRefreshing = true
        binding.tvEmpty.visibility = View.GONE
        binding.rvNotifications.visibility = View.VISIBLE

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val unified = RetrofitClient.instance(requireContext()).getUnifiedNotifications()
                val notifications = unified.data.data.orEmpty()
                adapter.submitList(notifications)
                Log.d("Notification", "Loaded ${notifications.size} unified notifications")
                if (notifications.isEmpty()) {
                    binding.tvEmpty.text = "No notifications yet"
                    binding.tvEmpty.visibility = View.VISIBLE
                    binding.rvNotifications.visibility = View.GONE
                } else {
                    binding.tvEmpty.visibility = View.GONE
                    binding.rvNotifications.visibility = View.VISIBLE
                }
            } catch (e: Exception) {
                showError("Network error. Pull down to retry.")
            } finally {
                binding.swipeRefresh.isRefreshing = false
            }
        }
    }


    private fun showError(message: String) {
        Toast.makeText(requireContext(), message, Toast.LENGTH_LONG).show()
        binding.tvEmpty.visibility = View.VISIBLE
        binding.tvEmpty.text = message
        binding.rvNotifications.visibility = View.GONE
    }

    private fun markAsRead(notificationId: String) {
        lifecycleScope.launch {
            try {
                RetrofitClient.instance(requireContext()).markUnifiedNotificationRead(notificationId)
            } catch (e: Exception) {
                // Silent fail
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}