#!/bin/sh
# Run admin:reset-passwords against the database defined in .env.prod.
# Backs up .env, swaps in .env.prod, runs the command, restores .env.
set -e
cd "$(dirname "$0")"
if [ ! -f .env.prod ]; then
  echo "Missing .env.prod"
  exit 1
fi
BACKUP=".env.bak.$$"
if [ -f .env ]; then
  cp .env "$BACKUP"
fi
cp .env.prod .env
php artisan admin:reset-passwords "SecurePassword123!"
if [ -f "$BACKUP" ]; then
  mv "$BACKUP" .env
fi
echo "Done. .env restored."
