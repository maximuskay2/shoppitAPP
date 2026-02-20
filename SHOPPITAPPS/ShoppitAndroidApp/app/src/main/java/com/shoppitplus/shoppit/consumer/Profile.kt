package com.shoppitplus.shoppit.consumer

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.bumptech.glide.Glide
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.activity.MainActivity
import com.shoppitplus.shoppit.databinding.FragmentProfileBinding
import com.shoppitplus.shoppit.databinding.ItemProfileRowBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import com.shoppitplus.shoppit.ui.TopBanner
import kotlinx.coroutines.launch

class Profile : Fragment() {
    private var _binding: FragmentProfileBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentProfileBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        loadUserProfile()

        // Set icons and labels for each included item
        setupMenuItem(binding.rowProfile, R.drawable.ic_profile, "Profile") {
            findNavController().navigate(R.id.action_profile_to_editProfile)
        }

        setupMenuItem(binding.rowAddress, R.drawable.ic_profile_location, "Address") {
            findNavController().navigate(R.id.action_profile_to_editAddress)
        }

        setupMenuItem(binding.rowAddress, R.drawable.ic_wallet, "Wallet") {
            findNavController().navigate(R.id.action_profile_to_wallet)
        }


        setupMenuItem(binding.rowOrders, R.drawable.ic_order, "Orders") {
            findNavController().navigate(R.id.action_profile_to_orders)

        }

        setupMenuItem(binding.rowPrivacy, R.drawable.ic_policy, "Privacy Policy") {

        }

        setupMenuItem(binding.rowSupport, R.drawable.ic_support, "Get Support") {
            val intent = Intent(Intent.ACTION_VIEW)
            intent.data = Uri.parse("https://wa.me/2348052187724")
            startActivity(intent)
        }

        setupMenuItem(binding.rowShare, R.drawable.ic_share, "Share App") {
            val intent = Intent(Intent.ACTION_SEND)
            intent.type = "text/plain"
            intent.putExtra(
                Intent.EXTRA_TEXT,
                "Download our app 👇 \n https://play.google.com/store/apps/details?id=${requireContext().packageName}"
            )
            startActivity(Intent.createChooser(intent, "Share app using"))
        }

        setupMenuItem(binding.rowRate, R.drawable.ic_star, "Rate Us") {
            val intent = Intent(Intent.ACTION_VIEW)
            intent.data = Uri.parse("market://details?id=${requireContext().packageName}")
            startActivity(intent)
        }

        setupMenuItem(binding.rowAbout, R.drawable.ic_info, "About App") {

        }

        setupMenuItem(binding.rowDelete, R.drawable.ic_delete, "Delete Account") {
            deleteAccountDialog()
        }

        binding.rowLogout.setOnClickListener { logoutDialog() }

    }

    private fun loadUserProfile() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).getUserAccount()
                if (response.success && response.data != null) {
                    val user = response.data

                    binding.txtName.text = user.name

                    Glide.with(requireContext())
                        .load(user.avatar)
                        .placeholder(R.drawable.user_image)
                        .error(R.drawable.user_image)
                        .into(binding.profileImage)
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Failed to load profile")
            }
        }
    }

    private fun logoutDialog() {
        AlertDialog.Builder(requireContext())
            .setTitle("Logout")
            .setMessage("Are you sure you want to logout?")
            .setPositiveButton("Yes") { _, _ ->
                logout()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun logout() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = RetrofitClient.instance(requireContext()).logout()

                clearSessionData()


                if (response.success) {
                    clearLocalSession()

                    TopBanner.showSuccess(
                        requireActivity(),
                        message = "Logged out",
                        subMessage = "You have been logged out successfully"
                    )
                    val intent = Intent(requireContext(), MainActivity::class.java)
                    intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                    activity?.startActivity(intent)


                    requireActivity().finishAffinity()
                } else {
                    TopBanner.showError(
                        requireActivity(),
                        "Logout failed",
                        response.message
                    )
                }
            } catch (e: Exception) {
                TopBanner.showError(requireActivity(), "Network error")
            }
        }
    }

    private fun clearLocalSession() {
        val prefs = requireActivity()
            .getSharedPreferences("info", Context.MODE_PRIVATE)
        prefs.edit().clear().apply()
    }

    private fun clearSessionData() {
        val prefs = requireContext().getSharedPreferences("info", Context.MODE_PRIVATE)
        val editor = prefs.edit()

        // DO NOT clear everything!
        // Only remove auth related keys
        editor.remove("auth_token")
        editor.remove("user_id")
        editor.remove("user_type")
        editor.remove("is_logged_in")
        // ... remove any other session keys you have

        editor.apply()
    }

    private fun setupMenuItem(
        rowBinding: ItemProfileRowBinding,
        icon: Int,
        title: String,
        action: () -> Unit
    ) {
        rowBinding.menuIcon.setImageResource(icon)
        rowBinding.menuTitle.text = title
        rowBinding.root.setOnClickListener { action() }
    }

    private fun deleteAccountDialog() {
        AlertDialog.Builder(requireContext())
            .setTitle("Delete Account?")
            .setMessage("This action is permanent. Do you want to continue?")
            .setPositiveButton("Delete") { _, _ -> }
            .setNegativeButton("Cancel", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
