package com.shoppitplus.shoppit.adapter

import android.content.res.ColorStateList
import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.recyclerview.widget.RecyclerView
import com.shoppitplus.shoppit.R
import com.shoppitplus.shoppit.utils.ProfileMenuItem

class ProfileMenuAdapter(
    private val items: List<ProfileMenuItem>,
    private val onItemClick: (ProfileMenuItem) -> Unit
) : RecyclerView.Adapter<ProfileMenuAdapter.MenuViewHolder>() {

    inner class MenuViewHolder(val view: View) : RecyclerView.ViewHolder(view) {
        val icon: ImageView = view.findViewById(R.id.menuIcon)
        val title: TextView = view.findViewById(R.id.menuTitle)
        val arrow: ImageView = view.findViewById(R.id.menuArrow)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): MenuViewHolder {
        val view = LayoutInflater.from(parent.context)
            .inflate(R.layout.item_profile_row, parent, false)
        return MenuViewHolder(view)
    }

    override fun onBindViewHolder(holder: MenuViewHolder, position: Int) {
        val item = items[position]

        holder.icon.setImageResource(item.iconRes)
        holder.title.text = item.title

        holder.title.setTextColor(
            if (item.isDestructive) Color.RED else Color.BLACK
        )
        holder.icon.imageTintList = ColorStateList.valueOf(
            if (item.isDestructive) Color.RED else Color.parseColor("#757575")
        )

        holder.arrow.visibility = if (item.navAction == null) View.GONE else View.VISIBLE

        holder.view.setOnClickListener { onItemClick(item) }
    }

    override fun getItemCount(): Int = items.size
}
