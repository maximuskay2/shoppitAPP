#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

TARGET="${1:-}"
API_BASE_URL="${API_BASE_URL:-}"
ENABLE_LOGS="${ENABLE_LOGS:-true}"

if [[ -z "$API_BASE_URL" ]]; then
  case "$TARGET" in
    android)
      API_BASE_URL="http://10.0.2.2/shopittplus-api/public/api/v1"
      ;;
    staging)
      API_BASE_URL="https://staging.shopittplus.org/api/v1"
      ;;
    prod|production)
      API_BASE_URL="https://shopittplus.espays.org/api/v1"
      ;;
    *)
      API_BASE_URL="http://localhost/shopittplus-api/public/api/v1"
      ;;
  esac
fi

flutter pub get
flutter run \
  --dart-define=API_BASE_URL="$API_BASE_URL" \
  --dart-define=ENABLE_LOGS="$ENABLE_LOGS"
