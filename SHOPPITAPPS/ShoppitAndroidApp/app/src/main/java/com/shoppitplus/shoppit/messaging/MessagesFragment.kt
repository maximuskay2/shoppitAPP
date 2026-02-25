package com.shoppitplus.shoppit.messaging

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.databinding.FragmentMessagesBinding
import com.shoppitplus.shoppit.shared.models.ConversationDto
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class MessagesFragment : Fragment() {

    private var _binding: FragmentMessagesBinding? = null
    private val binding get() = _binding!!
    private val apiClient = ShoppitApiClient()

    private var isVendor: Boolean = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        isVendor = arguments?.getBoolean(ARG_IS_VENDOR, false) ?: false
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentMessagesBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        binding.toolbar.setNavigationOnClickListener { findNavController().navigateUp() }
        binding.recyclerConversations.layoutManager = LinearLayoutManager(requireContext())
        binding.recyclerConversations.adapter = ConversationAdapter(emptyList()) { conv ->
            val otherName = conv.other?.name ?: "Driver"
            findNavController().navigate(
                R.id.action_messages_to_conversation,
                ConversationFragment.bundle(conv.id, otherName, isVendor)
            )
        }
        loadConversations()
    }

    private fun loadConversations() {
        val token = AppPrefs.getAuthToken(requireContext())
        if (token == null) {
            binding.progressBar.visibility = View.GONE
            binding.emptyText.visibility = View.VISIBLE
            binding.emptyText.text = "Please log in to view messages."
            return
        }

        binding.progressBar.visibility = View.VISIBLE
        binding.emptyText.visibility = View.GONE
        binding.recyclerConversations.visibility = View.GONE

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = if (isVendor) {
                    apiClient.getVendorConversations(token)
                } else {
                    apiClient.getConsumerConversations(token)
                }
                val list = response.data ?: emptyList()
                binding.recyclerConversations.adapter = ConversationAdapter(list) { conv ->
                    val otherName = conv.other?.name ?: "Driver"
                    findNavController().navigate(
                        R.id.action_messages_to_conversation,
                        ConversationFragment.bundle(conv.id, otherName, isVendor)
                    )
                }
                binding.progressBar.visibility = View.GONE
                if (list.isEmpty()) {
                    binding.emptyText.visibility = View.VISIBLE
                    binding.emptyText.text = "No conversations yet.\nMessages from drivers will appear here."
                } else {
                    binding.recyclerConversations.visibility = View.VISIBLE
                }
            } catch (e: Exception) {
                binding.progressBar.visibility = View.GONE
                binding.emptyText.visibility = View.VISIBLE
                binding.emptyText.text = "Failed to load messages."
                TopBanner.showError(requireActivity(), "Failed to load messages")
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    companion object {
        const val ARG_IS_VENDOR = "is_vendor"

        fun bundle(isVendor: Boolean): Bundle = Bundle().apply {
            putBoolean(ARG_IS_VENDOR, isVendor)
        }
    }
}
