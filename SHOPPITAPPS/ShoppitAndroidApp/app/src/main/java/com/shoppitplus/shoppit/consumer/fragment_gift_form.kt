package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.databinding.FragmentGiftFormBinding
import com.shoppitplus.shoppit.ui.TopBanner

class fragment_gift_form : Fragment() {

    private var _binding: FragmentGiftFormBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentGiftFormBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val infoPrefs = requireActivity()
            .getSharedPreferences("info", Context.MODE_PRIVATE)

        val cartPrefs = requireActivity()
            .getSharedPreferences("cart_prefs", Context.MODE_PRIVATE)

        // Sender name (locked)
        val userName = infoPrefs.getString("name", "") ?: ""
        binding.etSenderName.setText(userName)
        binding.etSenderName.isEnabled = false

        // Restore saved gift data
        binding.etRecipientName.setText(
            cartPrefs.getString("gift_recipient_name", "")
        )
        binding.etRecipientPhone.setText(
            cartPrefs.getString("gift_recipient_phone", "")
        )
        binding.etRecipientEmail.setText(
            cartPrefs.getString("gift_recipient_email", "")
        )
        binding.etGiftMessage.setText(
            cartPrefs.getString("gift_message", "")
        )

        binding.backButton.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.btnSaveGift.setOnClickListener {
            cartPrefs.edit().apply {
                putString("gift_sender_name", userName)
                putString(
                    "gift_recipient_name",
                    binding.etRecipientName.text.toString().trim()
                )
                putString(
                    "gift_recipient_phone",
                    binding.etRecipientPhone.text.toString().trim()
                )
                putString(
                    "gift_recipient_email",
                    binding.etRecipientEmail.text.toString().trim()
                )
                putString(
                    "gift_message",
                    binding.etGiftMessage.text.toString().trim()
                )
                apply()
            }

            TopBanner.showSuccess(
                requireActivity(),
                "Gift details saved"
            )

            findNavController().popBackStack()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
