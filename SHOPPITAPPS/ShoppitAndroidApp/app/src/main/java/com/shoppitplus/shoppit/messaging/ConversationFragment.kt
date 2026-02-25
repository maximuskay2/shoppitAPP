package com.shoppitplus.shoppit.messaging

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.recyclerview.widget.LinearLayoutManager
import com.shoppitplus.shoppit.databinding.FragmentConversationBinding
import com.shoppitplus.shoppit.shared.models.MessageDto
import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import com.shoppitplus.shoppit.ui.AppPrefs
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class ConversationFragment : Fragment() {

    private var _binding: FragmentConversationBinding? = null
    private val binding get() = _binding!!
    private val apiClient = ShoppitApiClient()

    private var conversationId: String = ""
    private var otherName: String = "Chat"
    private var isVendor: Boolean = false

    private lateinit var messageAdapter: MessageAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        conversationId = arguments?.getString("conversation_id") ?: ""
        otherName = arguments?.getString("other_name") ?: "Chat"
        isVendor = arguments?.getBoolean("is_vendor", false) ?: false
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentConversationBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        binding.toolbar.title = otherName
        binding.toolbar.setNavigationOnClickListener { findNavController().navigateUp() }

        messageAdapter = MessageAdapter(emptyList())
        binding.recyclerMessages.layoutManager = LinearLayoutManager(requireContext()).apply {
            stackFromEnd = true
        }
        binding.recyclerMessages.adapter = messageAdapter

        binding.btnSend.setOnClickListener { sendMessage() }

        loadMessages()
    }

    private fun loadMessages() {
        val token = AppPrefs.getAuthToken(requireContext())
        if (token == null) {
            binding.progressBar.visibility = View.GONE
            return
        }

        binding.progressBar.visibility = View.VISIBLE

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = if (isVendor) {
                    apiClient.getVendorMessages(token, conversationId)
                } else {
                    apiClient.getConsumerMessages(token, conversationId)
                }
                val messages = response.data?.messages ?: emptyList()
                messageAdapter.updateList(messages)
                binding.progressBar.visibility = View.GONE
                if (messages.isNotEmpty()) {
                    binding.recyclerMessages.smoothScrollToPosition(messages.size - 1)
                }
            } catch (e: Exception) {
                binding.progressBar.visibility = View.GONE
                TopBanner.showError(requireActivity(), "Failed to load messages")
            }
        }
    }

    private fun sendMessage() {
        val content = binding.inputMessage.text?.toString()?.trim() ?: return
        if (content.isEmpty()) return

        val token = AppPrefs.getAuthToken(requireContext()) ?: return

        binding.inputMessage.text?.clear()

        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = if (isVendor) {
                    apiClient.sendVendorMessage(token, conversationId, content)
                } else {
                    apiClient.sendConsumerMessage(token, conversationId, content)
                }
                response.data?.let { msg ->
                    messageAdapter.appendMessage(msg)
                    binding.recyclerMessages.smoothScrollToPosition(messageAdapter.itemCount - 1)
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Failed to send message")
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }

    companion object {
        const val ARG_CONVERSATION_ID = "conversation_id"
        const val ARG_OTHER_NAME = "other_name"
        const val ARG_IS_VENDOR = "is_vendor"

        fun bundle(conversationId: String, otherName: String, isVendor: Boolean): Bundle = Bundle().apply {
            putString("conversation_id", conversationId)
            putString("other_name", otherName)
            putBoolean("is_vendor", isVendor)
        }
    }
}
