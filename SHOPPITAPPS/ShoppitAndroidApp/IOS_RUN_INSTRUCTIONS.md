# Running Shoppit on iOS

The Shoppit app has been migrated to **Kotlin Multiplatform (KMM)** with Compose Multiplatform. The shared module builds successfully for both Android and iOS.

## KMM Migration Status

| Component | Status |
|-----------|--------|
| **shared** module | ✅ Complete – common code, Android, iOS targets |
| **Android app** | ✅ Complete – uses shared via `implementation(project(":shared"))` |
| **iOS framework** | ✅ Builds – `shared.framework` for simulator & device |
| **iOS app** | ⚠️ Requires Xcode project setup |

## Build Commands

```bash
# Android release
./gradlew :app:assembleRelease

# Shared module (Android AAR)
./gradlew :shared:assembleRelease

# iOS framework (simulator - Apple Silicon)
./gradlew :shared:linkReleaseFrameworkIosSimulatorArm64

# iOS framework (device)
./gradlew :shared:linkReleaseFrameworkIosArm64
```

## Running on iOS Simulator

### Option A: Create Xcode Project (Recommended)

1. **Open Xcode** and create a new project:
   - File → New → Project
   - Choose **App** under iOS
   - Product Name: `Shoppit`
   - Team: Select your team
   - Organization Identifier: `com.shoppitplus`
   - Interface: **SwiftUI**
   - Language: **Swift**
   - Save inside `iosApp/` folder

2. **Build the framework first:**
   ```bash
   cd /Applications/XAMPP/xamppfiles/htdocs/shopittplus-api/SHOPPITAPPS/ShoppitAndroidApp
   ./gradlew :shared:linkReleaseFrameworkIosSimulatorArm64
   ```

3. **Add framework to Xcode:**
   - Drag `shared/build/bin/iosSimulatorArm64/releaseFramework/shared.framework` into the Xcode project
   - Ensure "Copy items if needed" is **unchecked** (use reference)
   - Add to target: Shoppit

4. **Replace ContentView** with the one in `iosApp/iosApp/ContentView.swift`

5. **Add Build Phase** to build framework before compiling:
   - Target → Build Phases → + → New Run Script Phase
   - Name: "Build Kotlin Framework"
   - Script:
     ```bash
     set -e
     cd "$SRCROOT/../.."
     if [ "$EFFECTIVE_PLATFORM_NAME" = "-iphonesimulator" ]; then
       ./gradlew :shared:linkReleaseFrameworkIosSimulatorArm64
     else
       ./gradlew :shared:linkReleaseFrameworkIosArm64
     fi
     ```
   - Move this phase **before** "Compile Sources"

6. **Framework Search Paths** – ensure Xcode finds the right framework:
   - Target → Build Settings → search "Framework Search Paths"
   - For **Any iOS Simulator SDK**: `$(PROJECT_DIR)/../../shared/build/bin/iosSimulatorArm64/releaseFramework`
   - For **Any iOS SDK** (device): `$(PROJECT_DIR)/../../shared/build/bin/iosArm64/releaseFramework`
   - (Click the + next to Framework Search Paths to add SDK-specific entries)

7. **Run** on an iOS Simulator (e.g. iPhone 15) or a physical device

### Option B: Use Android Studio / IntelliJ

1. Add **Xcode Application** run configuration
2. Working directory: `iosApp/` (or path to your `.xcodeproj`)
3. Select an iOS Simulator and Run

## Framework Location

After building:
- **Simulator (Apple Silicon):** `shared/build/bin/iosSimulatorArm64/releaseFramework/shared.framework`
- **Device:** `shared/build/bin/iosArm64/releaseFramework/shared.framework`

## Troubleshooting

- **CADisableMinimumFrameDurationOnPhone:** Compose Multiplatform requires this for high-refresh-rate iPhones. Added to Info.plist. If missing, add in Xcode: Target → Info → Custom iOS Target Properties → + → `CADisableMinimumFrameDurationOnPhone` = `YES`.
- **Java heap space:** Increased to 6GB in `gradle.properties`. If it still fails, raise `-Xmx6144m` further.
- **Xcode version warning:** Add `kotlin.apple.xcodeCompatibility.nowarn=true` to `gradle.properties` to suppress.
- **AGP compatibility:** Add `kotlin.mpp.androidGradlePluginCompatibility.nowarn=true` to `gradle.properties` to suppress.
