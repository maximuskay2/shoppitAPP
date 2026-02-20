import java.util.Properties

plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

// Google Maps API key: prefer non-empty key from local.properties, then gradle.properties
val localProperties = Properties().apply {
    rootProject.file("local.properties").takeIf { it.exists() }?.reader(Charsets.UTF_8)?.use { load(it) }
}
val gradleProperties = Properties().apply {
    rootProject.file("gradle.properties").takeIf { it.exists() }?.reader(Charsets.UTF_8)?.use { load(it) }
}
val localKey = localProperties.getProperty("GOOGLE_MAPS_API_KEY")?.takeIf { it.isNotBlank() }?.trim()
val gradleKey = gradleProperties.getProperty("GOOGLE_MAPS_API_KEY")?.takeIf { it.isNotBlank() }?.trim()
val rawKey = when {
    !localKey.isNullOrEmpty() -> localKey
    !gradleKey.isNullOrEmpty() -> gradleKey
    else -> null
}
val googleMapsApiKey = rawKey ?: "YOUR_GOOGLE_MAPS_API_KEY"

android {
    namespace = "com.example.rider_flutter"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = flutter.ndkVersion

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
        isCoreLibraryDesugaringEnabled = true
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_17.toString()
    }

    defaultConfig {
        // TODO: Specify your own unique Application ID (https://developer.android.com/studio/build/application-id.html).
        applicationId = "com.example.rider_flutter"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
        manifestPlaceholders["googleMapsApiKey"] = googleMapsApiKey
    }

    buildTypes {
        release {
            // TODO: Add your own signing config for the release build.
            // Signing with the debug keys for now, so `flutter run --release` works.
            signingConfig = signingConfigs.getByName("debug")
        }
    }
}

dependencies {
    coreLibraryDesugaring("com.android.tools:desugar_jdk_libs:2.0.4")
}

flutter {
    source = "../.."
}
