package com.shoppitplus.shoppit.shared.di

import com.shoppitplus.shoppit.shared.network.ShoppitApiClient
import org.koin.core.context.startKoin
import org.koin.core.module.Module
import org.koin.dsl.KoinAppDeclaration
import org.koin.dsl.module

fun initKoin(appDeclaration: KoinAppDeclaration = {}) =
    startKoin {
        appDeclaration()
        modules(commonModule)
    }

// iOS entry point for Koin
fun initKoin() = initKoin {}

val commonModule = module {
    single { ShoppitApiClient() }
}
