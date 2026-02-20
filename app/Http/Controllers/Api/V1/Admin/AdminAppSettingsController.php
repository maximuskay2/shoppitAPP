<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAppSettingsController extends Controller
{
    /**
     * GET /api/v1/admin/settings/general
     * Returns app_name, contact_email, support_phone, app_logo for the admin UI.
     */
    public function general(): JsonResponse
    {
        $raw = Settings::getValue('general');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        return ShopittPlus::response(true, 'General settings retrieved', 200, [
            'app_name' => $data['app_name'] ?? '',
            'contact_email' => $data['contact_email'] ?? '',
            'support_phone' => $data['support_phone'] ?? '',
            'app_logo' => $data['app_logo'] ?? '',
        ]);
    }

    /**
     * POST /api/v1/admin/settings/general
     */
    public function storeGeneral(Request $request): JsonResponse
    {
        $request->validate([
            'app_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|string|max:255',
            'support_phone' => 'nullable|string|max:50',
            'app_logo' => 'nullable',
        ]);
        $payload = [
            'app_name' => $request->input('app_name', ''),
            'contact_email' => $request->input('contact_email', ''),
            'support_phone' => $request->input('support_phone', ''),
            'app_logo' => $request->input('app_logo', ''),
        ];
        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('settings', 'public');
            $payload['app_logo'] = asset('storage/' . $path);
        }
        $value = json_encode($payload);
        $setting = Settings::firstOrNew(['name' => 'general']);
        $setting->value = $value;
        $setting->description = 'General app settings';
        $setting->save();
        return ShopittPlus::response(true, 'General settings updated', 200, [
            'app_name' => $payload['app_name'],
            'contact_email' => $payload['contact_email'],
            'support_phone' => $payload['support_phone'],
            'app_logo' => $payload['app_logo'],
        ]);
    }

    /**
     * GET /api/v1/admin/settings/maps-api-key
     */
    public function mapsApiKey(): JsonResponse
    {
        $value = Settings::getValue('maps_api_key') ?? '';
        return ShopittPlus::response(true, 'Maps API key retrieved', 200, [
            'setting' => ['value' => $value],
        ]);
    }

    /**
     * GET /api/v1/admin/settings/fcm-tokens
     */
    public function fcmTokens(): JsonResponse
    {
        $raw = Settings::getValue('fcm_tokens');
        $tokens = $raw ? (json_decode($raw, true) ?: []) : [];
        if (!is_array($tokens)) {
            $tokens = [];
        }
        return ShopittPlus::response(true, 'FCM tokens retrieved', 200, [
            'tokens' => $tokens,
        ]);
    }

    /**
     * POST /api/v1/admin/settings/fcm-tokens
     */
    public function storeFcmTokens(Request $request): JsonResponse
    {
        $request->validate(['tokens' => 'array', 'tokens.*' => 'string']);
        $tokens = $request->input('tokens', []);
        $value = json_encode($tokens);
        $setting = Settings::firstOrNew(['name' => 'fcm_tokens']);
        $setting->value = $value;
        $setting->description = 'FCM server tokens';
        $setting->save();
        return ShopittPlus::response(true, 'FCM tokens updated', 200, ['tokens' => $tokens]);
    }

    /**
     * GET /api/v1/admin/settings/smtp
     */
    public function smtp(): JsonResponse
    {
        $raw = Settings::getValue('smtp');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        return ShopittPlus::response(true, 'SMTP settings retrieved', 200, [
            'mailer' => $data['mailer'] ?? env('MAIL_MAILER', 'smtp'),
            'host' => $data['host'] ?? env('MAIL_HOST', ''),
            'port' => $data['port'] ?? env('MAIL_PORT', ''),
            'username' => $data['username'] ?? env('MAIL_USERNAME', ''),
            'password' => '', // Never expose stored password
            'encryption' => $data['encryption'] ?? env('MAIL_ENCRYPTION', ''),
            'from_address' => $data['from_address'] ?? env('MAIL_FROM_ADDRESS', ''),
            'from_name' => $data['from_name'] ?? env('MAIL_FROM_NAME', ''),
        ]);
    }

    /**
     * POST /api/v1/admin/settings/smtp
     */
    public function storeSmtp(Request $request): JsonResponse
    {
        $request->validate([
            'mailer' => 'nullable|string|max:50',
            'host' => 'nullable|string|max:255',
            'port' => 'nullable|string|max:10',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'encryption' => 'nullable|string|in:tls,ssl,',
            'from_address' => 'nullable|email',
            'from_name' => 'nullable|string|max:255',
        ]);

        $raw = Settings::getValue('smtp');
        $existing = $raw ? (json_decode($raw, true) ?: []) : [];

        $payload = [
            'mailer' => $request->input('mailer', 'smtp'),
            'host' => $request->input('host', ''),
            'port' => $request->input('port', '587'),
            'username' => $request->input('username', ''),
            'password' => $request->filled('password') ? $request->input('password') : ($existing['password'] ?? ''),
            'encryption' => $request->input('encryption', 'tls'),
            'from_address' => $request->input('from_address', ''),
            'from_name' => $request->input('from_name', ''),
        ];

        $response = $payload;
        $response['password'] = ''; // Never return password in response

        $value = json_encode($payload);
        $setting = Settings::firstOrNew(['name' => 'smtp']);
        $setting->value = $value;
        $setting->description = 'SMTP mail configuration';
        $setting->save();

        return ShopittPlus::response(true, 'SMTP settings updated', 200, $response);
    }

    /**
     * GET /api/v1/admin/settings/ebulksms
     */
    public function ebulksms(): JsonResponse
    {
        $raw = Settings::getValue('ebulksms');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        return ShopittPlus::response(true, 'EbulkSMS settings retrieved', 200, [
            'base_url' => $data['base_url'] ?? env('EBULKSMS_BASE_URL', 'https://api.ebulksms.com/sendsms.json'),
            'username' => $data['username'] ?? env('EBULKSMS_USERNAME', ''),
            'api_key' => '', // Never expose stored API key
            'sender' => $data['sender'] ?? env('EBULKSMS_SENDER', 'ShopittPlus'),
            'dndsender' => $data['dndsender'] ?? env('EBULKSMS_DNDSENDER', 0),
            'country_code' => $data['country_code'] ?? env('EBULKSMS_COUNTRY_CODE', '234'),
        ]);
    }

    /**
     * POST /api/v1/admin/settings/ebulksms
     */
    public function storeEbulksms(Request $request): JsonResponse
    {
        $request->validate([
            'base_url' => 'nullable|string|max:500',
            'username' => 'nullable|string|max:255',
            'api_key' => 'nullable|string|max:255',
            'sender' => 'nullable|string|max:11',
            'dndsender' => 'nullable|integer|in:0,1',
            'country_code' => 'nullable|string|max:5',
        ]);

        $raw = Settings::getValue('ebulksms');
        $existing = $raw ? (json_decode($raw, true) ?: []) : [];

        $payload = [
            'base_url' => $request->input('base_url', 'https://api.ebulksms.com/sendsms.json'),
            'username' => $request->input('username', ''),
            'api_key' => $request->filled('api_key') ? $request->input('api_key') : ($existing['api_key'] ?? ''),
            'sender' => $request->input('sender', 'ShopittPlus'),
            'dndsender' => (int) ($request->input('dndsender', 0)),
            'country_code' => $request->input('country_code', '234'),
        ];

        $response = $payload;
        $response['api_key'] = ''; // Never return API key in response

        $value = json_encode($payload);
        $setting = Settings::firstOrNew(['name' => 'ebulksms']);
        $setting->value = $value;
        $setting->description = 'EbulkSMS configuration for OTP and notifications';
        $setting->save();

        return ShopittPlus::response(true, 'EbulkSMS settings updated', 200, $response);
    }

    /**
     * GET /api/v1/admin/settings/cloudinary
     */
    public function cloudinary(): JsonResponse
    {
        $raw = Settings::getValue('cloudinary');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];
        return ShopittPlus::response(true, 'Cloudinary settings retrieved', 200, [
            'cloud_name' => $data['cloud_name'] ?? env('CLOUDINARY_CLOUD_NAME', ''),
            'api_key' => $data['api_key'] ?? env('CLOUDINARY_API_KEY', ''),
            'api_secret' => '', // Never expose stored secret
        ]);
    }

    /**
     * POST /api/v1/admin/settings/cloudinary
     */
    public function storeCloudinary(Request $request): JsonResponse
    {
        $request->validate([
            'cloud_name' => 'required|string|max:255',
            'api_key' => 'required|string|max:255',
            'api_secret' => 'nullable|string|max:255',
        ]);

        $raw = Settings::getValue('cloudinary');
        $existing = $raw ? (json_decode($raw, true) ?: []) : [];

        $payload = [
            'cloud_name' => $request->input('cloud_name'),
            'api_key' => $request->input('api_key'),
            'api_secret' => $request->filled('api_secret')
                ? $request->input('api_secret')
                : ($existing['api_secret'] ?? ''),
        ];

        $response = [
            'cloud_name' => $payload['cloud_name'],
            'api_key' => $payload['api_key'],
            'api_secret' => '', // Never return secret in response
        ];

        $value = json_encode($payload);
        $setting = Settings::firstOrNew(['name' => 'cloudinary']);
        $setting->value = $value;
        $setting->description = 'Cloudinary config for document/images uploads';
        $setting->save();

        return ShopittPlus::response(true, 'Cloudinary settings updated', 200, $response);
    }
}
