package com.shoppitplus.shoppit.ui

object CartManager {
    private var cartItemCount = 0
    private val listeners = mutableListOf<() -> Unit>()

    fun getItemCount() = cartItemCount

    fun setItemCount(count: Int) {
        cartItemCount = count
        notifyListeners()
    }

    fun addListener(listener: () -> Unit) {
        listeners.add(listener)
    }

    fun removeListener(listener: () -> Unit) {
        listeners.remove(listener)
    }

    private fun notifyListeners() {
        listeners.forEach { it.invoke() }
    }

    fun increment() {
        cartItemCount++
        notifyListeners()
    }

    fun decrement() {
        if (cartItemCount > 0) cartItemCount--
        notifyListeners()
    }
}