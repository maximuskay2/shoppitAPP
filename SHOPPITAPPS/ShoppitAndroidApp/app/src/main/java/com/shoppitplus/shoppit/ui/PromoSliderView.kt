package com.shoppitplus.shoppit.ui

import android.os.Handler
import android.os.Looper
import android.widget.ImageView
import android.widget.LinearLayout
import androidx.core.content.ContextCompat
import androidx.viewpager2.widget.ViewPager2
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.adapter.PromoSliderAdapter
import com.shoppitplus.shoppit.onboarding.PromoSlide

class PromoSliderView(
    private val viewPager: ViewPager2,
    private val dotsLayout: LinearLayout,
    private val slides: List<PromoSlide>
) {

    private val handler = Handler(Looper.getMainLooper())
    private var runnable: Runnable? = null
    private val interval = 4000L // 4 seconds

    fun setupSlider() {
        viewPager.adapter = PromoSliderAdapter(slides)
        setupIndicators()
        setCurrentIndicator(0)

        viewPager.registerOnPageChangeCallback(object : ViewPager2.OnPageChangeCallback() {
            override fun onPageSelected(position: Int) {
                super.onPageSelected(position)
                setCurrentIndicator(position)
            }
        })

        // Auto-scroll runnable
        runnable = Runnable {
            val next = (viewPager.currentItem + 1) % slides.size
            viewPager.setCurrentItem(next, true)
            handler.postDelayed(runnable!!, interval)
        }
        handler.postDelayed(runnable!!, interval)
    }

    private fun setupIndicators() {
        dotsLayout.removeAllViews()
        val indicators = Array(slides.size) { ImageView(viewPager.context) }
        val params = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.WRAP_CONTENT,
            LinearLayout.LayoutParams.WRAP_CONTENT
        )
        params.setMargins(6, 0, 6, 0)
        for (i in indicators.indices) {
            indicators[i].apply {
                setImageDrawable(
                    ContextCompat.getDrawable(
                        viewPager.context,
                        R.drawable.indicator_inactive
                    )
                )
                layoutParams = params
            }
            dotsLayout.addView(indicators[i])
        }
    }

    private fun setCurrentIndicator(index: Int) {
        val childCount = dotsLayout.childCount
        for (i in 0 until childCount) {
            val imageView = dotsLayout.getChildAt(i) as ImageView
            if (i == index) {
                imageView.setImageDrawable(
                    ContextCompat.getDrawable(viewPager.context, R.drawable.indicator_active)
                )
            } else {
                imageView.setImageDrawable(
                    ContextCompat.getDrawable(viewPager.context, R.drawable.indicator_inactive)
                )
            }
        }
    }

    fun stopAutoScroll() {
        runnable?.let { handler.removeCallbacks(it) }
    }
}
