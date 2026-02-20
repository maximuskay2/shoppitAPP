package com.shoppitplus.shoppit.adapter

import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.onboarding.PromoSlide

class PromoSliderAdapter(private val slides: List<PromoSlide>) :
    RecyclerView.Adapter<PromoSliderAdapter.SliderViewHolder>() {

    inner class SliderViewHolder(itemView: View) : RecyclerView.ViewHolder(itemView) {
        val title: TextView = itemView.findViewById(R.id.promoTitle)
        val desc: TextView = itemView.findViewById(R.id.promoDesc)
        val image: ImageView = itemView.findViewById(R.id.promoImage)
        val button: Button = itemView.findViewById(R.id.shopNowBtn)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): SliderViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_promo_slide, parent, false)
        return SliderViewHolder(view)
    }

    override fun onBindViewHolder(holder: SliderViewHolder, position: Int) {
        val slide = slides[position]

        holder.title.text = slide.title
        holder.desc.text = slide.description
        holder.image.setImageResource(slide.imageRes)

        if (slide.showButton) {
            holder.button.visibility = View.VISIBLE
            holder.button.text = slide.buttonText
        } else {
            holder.button.visibility = View.GONE
        }

        // Optional: handle button click for each slide
        holder.button.setOnClickListener {
            // Example: Log or navigate
            // Log.d("PromoSlider", "Clicked: ${slide.buttonText}")
        }
    }

    override fun getItemCount(): Int = slides.size
}
