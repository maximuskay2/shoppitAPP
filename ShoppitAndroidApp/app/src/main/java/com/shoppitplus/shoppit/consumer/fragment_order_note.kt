package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.databinding.FragmentOrderNoteBinding
import com.shoppitplus.shoppit.ui.TopBanner

class fragment_order_note : Fragment() {

    private var _binding: FragmentOrderNoteBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentOrderNoteBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val prefs = requireActivity()
            .getSharedPreferences("cart_prefs", Context.MODE_PRIVATE)

        // Restore saved note
        binding.etOrderNote.setText(
            prefs.getString("order_note", "")
        )

        binding.backButton.setOnClickListener {
            findNavController().popBackStack()
        }

        binding.btnSaveNote.setOnClickListener {
            val note = binding.etOrderNote.text.toString().trim()

            prefs.edit()
                .putString("order_note", note)
                .apply()

            TopBanner.showSuccess(
                requireActivity(),
                "Message saved"
            )

            findNavController().popBackStack()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
