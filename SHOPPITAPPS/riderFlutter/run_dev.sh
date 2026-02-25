#!/usr/bin/env bash
# Run the driver app for local testing.
#
# Prerequisites:
#   - android: XAMPP Apache running and serving the Laravel app at
#     http://localhost/shopittplus-api/public/ (or use android-artisan).
#   - android-artisan: In another terminal, run: cd /path/to/shopittplus-api && php artisan serve
#
# Usage:
#   ./run_dev.sh              # uses XAMPP URL (localhost path)
#   ./run_dev.sh android      # same, explicit (emulator: 10.0.2.2/shopittplus-api/public)
#   ./run_dev.sh android-artisan  # emulator → 10.0.2.2:8000 (requires php artisan serve)
#   ./run_dev.sh staging      # staging server
#   ./run_dev.sh prod         # production server
#
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_ROOT"

TARGET="${1:-}"
API_BASE_URL="${API_BASE_URL:-}"
ENABLE_LOGS="${ENABLE_LOGS:-true}"

if [[ -z "$API_BASE_URL" ]]; then
  case "$TARGET" in
    android)
      # XAMPP: ensure Apache is running and serving shopittplus-api/public
      API_BASE_URL="http://10.0.2.2/shopittplus-api/public/api/v1"
      ;;
    android-artisan)
      API_BASE_URL="http://10.0.2.2:8000/api/v1"
      LARAVEL_ROOT="$(cd "$PROJECT_ROOT/.." && pwd)"
      if ! curl -s -o /dev/null -w '%{http_code}' 'http://127.0.0.1:8000/up' 2>/dev/null | grep -qE '200|404|500'; then
        echo ""
        echo "  >>> Start the API first (in another terminal):"
        echo "      cd $LARAVEL_ROOT && php artisan serve"
        echo ""
      fi
      ;;
    staging)
      API_BASE_URL="https://staging.shopittplus.org/api/v1"
      ;;
    prod|production)
      API_BASE_URL="https://laravelapi-production-1ea4.up.railway.app/api/v1"
      ;;
    *)
      API_BASE_URL="http://localhost/shopittplus-api/public/api/v1"
      ;;
  esac
fi

echo "API_BASE_URL=$API_BASE_URL"
flutter pub get
flutter run \
  --dart-define=API_BASE_URL="$API_BASE_URL" \
  --dart-define=ENABLE_LOGS="$ENABLE_LOGS"
