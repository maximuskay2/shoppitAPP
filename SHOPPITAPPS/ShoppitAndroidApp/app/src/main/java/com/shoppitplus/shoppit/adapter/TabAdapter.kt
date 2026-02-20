package com.shoppitplus.shoppit.adapter

import androidx.fragment.app.Fragment
import androidx.fragment.app.FragmentManager
import androidx.lifecycle.Lifecycle
import androidx.viewpager2.adapter.FragmentStateAdapter

class TabAdapter(
    fragmentManager: FragmentManager,
    lifecycle: Lifecycle,
    private val fragments: List<Pair<String, () -> Fragment>>
) : FragmentStateAdapter(fragmentManager, lifecycle) {

    override fun getItemCount(): Int = fragments.size

    override fun createFragment(position: Int): Fragment {
        return fragments[position].second()
    }

    fun getTabTitle(position: Int): String = fragments[position].first
}