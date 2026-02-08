#!/usr/bin/env bash
set -euo pipefail

BASE_URL="http://localhost/shopittplus-api/public/api/v1"

ADMIN_RESP=$(curl -sS -X POST "$BASE_URL/admin/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"localadmin@example.com","password":"Alliswell34"}')

ADMIN_TOKEN=$(printf "%s" "$ADMIN_RESP" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("data",{}).get("token",""))')

if [ -z "$ADMIN_TOKEN" ]; then
  echo "Admin login failed. Response:" && echo "$ADMIN_RESP"
  exit 1
fi

echo "Admin login ok. Updating driver app settings..."
UPDATE_RESP=$(curl -sS -X PUT "$BASE_URL/admin/settings/driver-app" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $ADMIN_TOKEN" \
  -d '{"force_update":true,"min_version":"1.1.0","latest_version":"1.1.0","message":"Update your app now"}')

if ! echo "$UPDATE_RESP" | python3 -m json.tool; then
  echo "$UPDATE_RESP"
  exit 1
fi

DRIVER_RESP=$(curl -sS -X POST "$BASE_URL/driver/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"driver.test@shopittplus.dev","password":"Password123!"}')

DRIVER_TOKEN=$(printf "%s" "$DRIVER_RESP" | python3 -c 'import json,sys; print(json.load(sys.stdin).get("data",{}).get("token",""))')

if [ -z "$DRIVER_TOKEN" ]; then
  echo "Driver login failed. Response:" && echo "$DRIVER_RESP"
  exit 1
fi

echo "Driver login ok. Fetching /driver/app-config..."
curl -sS -H "Authorization: Bearer $DRIVER_TOKEN" "$BASE_URL/driver/app-config" | python3 -m json.tool
