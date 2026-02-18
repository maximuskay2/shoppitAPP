// This file is used to override the API base URL for Android emulator development.
// 10.0.2.2 is the emulator's alias for the host machine's localhost.
//
// Option A: XAMPP – ensure Apache routes /shopittplus-api/public to Laravel.
// Option B: php artisan serve – run from project root, then use kEmulatorApiBaseUrlArtisan.
const String kEmulatorApiBaseUrl = "http://10.0.2.2/shopittplus-api/public/api/v1";
const String kEmulatorApiBaseUrlArtisan = "http://10.0.2.2:8000/api/v1";