package com.shoppitplus.shoppit.ui

import android.app.Activity
import android.view.LayoutInflater
import com.shoppitplus.shoppit.R
import android.view.View
import android.view.ViewGroup
import android.view.animation.AccelerateDecelerateInterpolator
import android.widget.ImageView
import android.widget.TextView

object TopBanner {

    fun show(
        activity: Activity,
        message: String,
        subMessage: String = "",
        iconRes: Int = R.drawable.check_circle,
        backgroundRes: Int = R.drawable.btn_banner,
        duration: Long = 5000
    ) {
        val rootView = activity.findViewById<ViewGroup>(android.R.id.content)

        // Inflate banner layout
        val bannerView =
            LayoutInflater.from(activity).inflate(R.layout.layout_top_banner, rootView, false)

        val bannerRoot = bannerView.findViewById<View>(R.id.banner_root)
        val icon = bannerView.findViewById<ImageView>(R.id.banner_icon)
        val messageTv = bannerView.findViewById<TextView>(R.id.banner_message)
        val subMessageTv = bannerView.findViewById<TextView>(R.id.banner_sub_message)

        icon.setImageResource(iconRes)
        bannerRoot.setBackgroundResource(backgroundRes)
        messageTv.text = message
        subMessageTv.text = subMessage

        rootView.addView(bannerView, 0)

        bannerRoot.visibility = View.VISIBLE
        bannerRoot.translationY = -bannerRoot.height.toFloat()
        bannerRoot.animate()
            .translationY(0f)
            .setDuration(400)
            .setInterpolator(AccelerateDecelerateInterpolator())
            .start()

        // Auto dismiss after duration
        bannerRoot.postDelayed({
            bannerRoot.animate()
                .translationY(-bannerRoot.height.toFloat())
                .setDuration(400)
                .withEndAction { rootView.removeView(bannerView) }
                .start()
        }, duration)
    }

    fun showSuccess(activity: Activity, message: String, subMessage: String = "") {
        show(
            activity,
            message,
            subMessage,
            R.drawable.check_circle,
            R.drawable.btn_banner
        )
    }

    fun showError(activity: Activity, message: String, subMessage: String = "") {
        show(
            activity,
            message,
            subMessage,
            R.drawable.check_circle,
            R.drawable.btn_banner_error
        )
    }
}