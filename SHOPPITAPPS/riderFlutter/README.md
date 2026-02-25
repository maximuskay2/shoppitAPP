# rider_flutter

Driver/rider Flutter app for Shoppit.

## API base URL

- **Production / release:** The app uses `https://laravelapi-production-1ea4.up.railway.app/api/v1` when no `API_BASE_URL` is passed (e.g. `flutter build apk` or `flutter build appbundle`).
- **Dev:** Use `./run_dev.sh android`, `./run_dev.sh prod`, or `flutter run --dart-define=API_BASE_URL=<url>`.

## Release builds

```bash
# APK (uses production API by default)
flutter build apk

# App Bundle for Play Store
flutter build appbundle
```

To point a release build at a different API, pass the URL:

```bash
flutter build apk --dart-define=API_BASE_URL=https://your-api.example.com/api/v1
```

## Getting Started

This project is a starting point for a Flutter application.

A few resources to get you started if this is your first Flutter project:

- [Lab: Write your first Flutter app](https://docs.flutter.dev/get-started/codelab)
- [Cookbook: Useful Flutter samples](https://docs.flutter.dev/cookbook)

For help getting started with Flutter development, view the
[online documentation](https://docs.flutter.dev/), which offers tutorials,
samples, guidance on mobile development, and a full API reference.
