plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.google.gms.google.services)
    alias(libs.plugins.google.firebase.crashlytics)
    id("org.jetbrains.kotlin.plugin.compose") version "2.0.0"
}

android {
    namespace = "com.shoppitplus.shoppit"
    compileSdk = 36

    defaultConfig {
        applicationId = "com.shoppitplus.shoppit"
        minSdk = 23
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    signingConfigs {
        create("release") {
            storeFile = file(System.getenv("KEYSTORE_PATH") ?: "/Applications/XAMPP/xamppfiles/htdocs/shopittplus-api/Untitled.jks")
            storePassword = System.getenv("KEYSTORE_PASSWORD") ?: "Alliswell34"
            keyAlias = System.getenv("KEY_ALIAS") ?: "shoppit"
            keyPassword = System.getenv("KEY_PASSWORD") ?: "Alliswell34"
        }
    }

    buildTypes {
        debug {
            buildConfigField("String", "BASE_URL", "\"http://10.0.2.2/shopittplus-api/public/api/v1/\"")
        }
        release {
            isMinifyEnabled = false
            buildConfigField("String", "BASE_URL", "\"https://laravelapi-production-1ea4.up.railway.app/api/v1/\"")
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            signingConfig = signingConfigs.getByName("release")
            // Disable baseline profile to prevent INSTALL_BASELINE_PROFILE_FAILED on some devices
            installation {
                enableBaselineProfile = false
            }
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    kotlinOptions {
        jvmTarget = "11"
    }

    buildFeatures {
        viewBinding = true
        buildConfig = true
        compose = true
    }

    packaging {
        resources {
            // Exclude baseline profiles to prevent installation failures on emulators
            excludes += "/assets/dexopt/baseline.prof"
            excludes += "/assets/dexopt/baseline.profm"
        }
    }
}

dependencies {
    // --- LINKING SHARED KMP MODULE ---
    implementation(project(":shared"))

    // --- JETPACK COMPOSE & MATERIAL 3 ---
    val composeBom = platform("androidx.compose:compose-bom:2026.01.01")
    implementation(composeBom)
    implementation("androidx.compose.ui:ui")
    implementation("androidx.compose.material3:material3")
    implementation("androidx.compose.ui:ui-tooling-preview")
    implementation("androidx.activity:activity-compose")
    implementation("androidx.lifecycle:lifecycle-viewmodel-compose")

    // --- EXISTING LIBRARIES ---
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.appcompat)
    implementation(libs.material)
    implementation(libs.androidx.activity)
    implementation(libs.androidx.constraintlayout)
    implementation(libs.androidx.fragment)
    implementation(libs.androidx.navigation.fragment.ktx)
    implementation(libs.androidx.navigation.ui.ktx)
    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)

    implementation("de.hdodenhof:circleimageview:3.1.0")
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.9.3")

    implementation("com.github.bumptech.glide:glide:4.11.0")
    annotationProcessor("com.github.bumptech.glide:compiler:4.11.0")

    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.3.0")
    implementation("androidx.lifecycle:lifecycle-livedata-ktx:2.3.0")
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.3.0")

    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-core:1.3.5")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.3.5")
    implementation("org.jetbrains.kotlinx:kotlinx-serialization-json:1.6.3")

    implementation("com.tbuonomo:dotsindicator:5.0")
    implementation("androidx.swiperefreshlayout:swiperefreshlayout:1.1.0")
    implementation("com.google.android.flexbox:flexbox:3.0.0")
    implementation("androidx.biometric:biometric:1.1.0")
}
