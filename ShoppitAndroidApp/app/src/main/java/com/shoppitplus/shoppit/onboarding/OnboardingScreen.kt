package com.shoppitplus.shoppit.onboarding

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import androidx.viewpager2.widget.ViewPager2
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.OnboardingImageAdapter
import com.shoppitplus.shoppit.databinding.FragmentOnboardingScreenBinding

class OnboardingScreen : Fragment() {
    private var _binding: FragmentOnboardingScreenBinding? = null
    private val binding get() = _binding!!

    private val onboardingTitles = listOf(
        "Your All-in-One Marketplace.",
        "Shop Easily from Nearby Stores.",
        "Sell Smarter. Reach More Buyers."
    )

    private val onboardingSubtitles = listOf(
        "Buy, sell, and deliver from one platform for local businesses.",
        "Discover trusted local vendors, compare prices, and get your orders delivered fast and safely.",
        "List your products, manage orders, and grow your business — all from your Shopittplus dashboard."
    )

    private val onboardingImages = listOf(
        R.drawable.first_image,
        R.drawable.second_image,
        R.drawable.third_image
    )

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentOnboardingScreenBinding.inflate(inflater, container, false)

        val adapter = OnboardingImageAdapter(onboardingImages)
        binding.onboardingImage.adapter = adapter
        binding.indicatorLayout.attachTo(binding.onboardingImage)

        binding.textTitle.text = onboardingTitles[0]
        binding.textSubtitle.text = onboardingSubtitles[0]

        binding.onboardingImage.registerOnPageChangeCallback(object :
            ViewPager2.OnPageChangeCallback() {
            override fun onPageSelected(position: Int) {
                super.onPageSelected(position)
                binding.textTitle.text = onboardingTitles[position]
                binding.textSubtitle.text = onboardingSubtitles[position]
            }
        })

        binding.btnLogin.setOnClickListener {
            findNavController().navigate(R.id.action_onboardingScreen_to_login)
        }

         binding.skipText.setOnClickListener {
            findNavController().navigate(R.id.action_onboardingScreen_to_login)
        }



         binding.btnRegister.setOnClickListener {
            findNavController().navigate(R.id.action_onboardingScreen_to_createAccount)
        }



        return binding.root
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}