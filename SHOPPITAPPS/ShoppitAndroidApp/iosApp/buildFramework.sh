#!/bin/bash
set -e
cd "$(dirname "$0")/.."
./gradlew :shared:linkReleaseFrameworkIosSimulatorArm64
echo "Framework built at: shared/build/bin/iosSimulatorArm64/releaseFramework/shared.framework"
