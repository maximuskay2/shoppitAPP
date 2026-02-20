package com.shoppitplus.shoppit

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import androidx.fragment.app.Fragment
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.shoppitplus.shoppit.databinding.FragmentManageBinding
import com.shoppitplus.shoppit.databinding.ItemProfileRowBinding
import com.shoppitplus.shoppit.models.RetrofitClient
import kotlinx.coroutines.launch


class Manage : Fragment() {
    private var _binding: FragmentManageBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View? {
        // Inflate the layout for this fragment
        _binding = FragmentManageBinding.inflate(inflater, container, false)
        setupMenu()
        loadSubscription()
        return binding.root
    }

    private fun setupMenu() {

        setup(binding.rowEditBusiness, R.drawable.ic_edit, "Edit Business Profile") {
            //   findNavController().navigate(R.id.action_vendorManage_to_editBusiness)
        }

        setup(binding.rowStoreHours, R.drawable.ic_profile, "Store Hours") {
            startActivity(Intent(requireContext(), com.shoppitplus.shoppit.vendor.StoreHoursActivity::class.java))
        }

        setup(binding.rowProducts, R.drawable.ic_product, "Products") {
            //    findNavController().navigate(R.id.action_vendorManage_to_products)
        }

        setup(binding.rowSubscription, R.drawable.ic_subscription, "Subscription Plan") {
            //  findNavController().navigate(R.id.action_vendorManage_to_subscription)
        }

        setup(binding.rowCoupon, R.drawable.ic_coupon, "Coupon Discount") {}

        setup(binding.rowShareStore, R.drawable.ic_share, "Share Store Link") {
            shareStore()
        }

        setup(binding.rowPrivacy, R.drawable.ic_policy, "Privacy Policy") {}

        setup(binding.rowSupport, R.drawable.ic_support, "Get Support") {
            openWhatsApp()
        }

        setup(binding.rowShareApp, R.drawable.ic_share, "Share App") {
            shareApp()
        }

        setup(binding.rowRate, R.drawable.ic_star, "Rate Us") {
            rateApp()
        }

        setup(binding.rowAbout, R.drawable.ic_info, "About App") {}

        setup(binding.rowDelete, R.drawable.ic_delete, "Delete Account") {}

        binding.btnLogout.setOnClickListener { logout() }
    }

    private fun loadSubscription() {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val response = RetrofitClient
                    .instance(requireContext())
                    .getVendorSubscription()

                val plan = response.data.plan
                binding.rowSubscription.menuBadge.apply {
                    text = "Tier ${plan.key}"
                    visibility = View.VISIBLE
                }
            } catch (_: Exception) {
            }
        }
    }

    private fun setup(
        row: ItemProfileRowBinding,
        icon: Int,
        title: String,
        action: () -> Unit
    ) {
        row.menuIcon.setImageResource(icon)
        row.menuTitle.text = title
        row.root.setOnClickListener { action() }
    }

    private fun shareStore() {
        val intent = Intent(Intent.ACTION_SEND)
        intent.type = "text/plain"
        intent.putExtra(Intent.EXTRA_TEXT, "https://ownshop.io/killmanjaro")
        startActivity(Intent.createChooser(intent, "Share store"))
    }

    private fun shareApp() {
        val intent = Intent(Intent.ACTION_SEND)
        intent.type = "text/plain"
        intent.putExtra(
            Intent.EXTRA_TEXT,
            "Download the app https://play.google.com/store/apps/details?id=${requireContext().packageName}"
        )
        startActivity(intent)
    }

    private fun openWhatsApp() {
        startActivity(Intent(Intent.ACTION_VIEW, Uri.parse("https://wa.me/2348052187724")))
    }

    private fun rateApp() {
        startActivity(
            Intent(
                Intent.ACTION_VIEW,
                Uri.parse("market://details?id=${requireContext().packageName}")
            )
        )
    }

    private fun logout() {
        // reuse your existing logout logic
    }
}





