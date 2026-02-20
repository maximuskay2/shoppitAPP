<?php

namespace App\Helpers;

use App\Modules\Commerce\Models\Settings;
use Illuminate\Support\Facades\Config;

class RuntimeConfig
{
    /**
     * Get SMTP config - database settings override env when present.
     */
    public static function getSmtpConfig(): array
    {
        $raw = Settings::getValue('smtp');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];

        $host = $data['host'] ?? env('MAIL_HOST', '');
        $username = $data['username'] ?? env('MAIL_USERNAME', '');
        $password = $data['password'] ?? env('MAIL_PASSWORD', '');

        return [
            'mailer' => $data['mailer'] ?? env('MAIL_MAILER', 'smtp'),
            'host' => $host,
            'port' => $data['port'] ?? env('MAIL_PORT', '587'),
            'username' => $username,
            'password' => $password,
            'encryption' => $data['encryption'] ?? env('MAIL_ENCRYPTION', 'tls'),
            'from_address' => $data['from_address'] ?? env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'from_name' => $data['from_name'] ?? env('MAIL_FROM_NAME', config('app.name')),
        ];
    }

    /**
     * Get EbulkSMS config - database settings override env when present.
     */
    public static function getEbulksmsConfig(): array
    {
        $raw = Settings::getValue('ebulksms');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];

        return [
            'base_url' => $data['base_url'] ?? env('EBULKSMS_BASE_URL', 'https://api.ebulksms.com/sendsms.json'),
            'username' => $data['username'] ?? env('EBULKSMS_USERNAME', ''),
            'api_key' => $data['api_key'] ?? env('EBULKSMS_API_KEY', ''),
            'sender' => $data['sender'] ?? env('EBULKSMS_SENDER', 'ShopittPlus'),
            'dndsender' => $data['dndsender'] ?? (int) env('EBULKSMS_DNDSENDER', 0),
            'country_code' => $data['country_code'] ?? env('EBULKSMS_COUNTRY_CODE', '234'),
        ];
    }

    /**
     * Get Cloudinary config - database settings override env when present.
     */
    public static function getCloudinaryConfig(): array
    {
        $raw = Settings::getValue('cloudinary');
        $data = $raw ? (json_decode($raw, true) ?: []) : [];

        $cloudName = $data['cloud_name'] ?? env('CLOUDINARY_CLOUD_NAME', '');
        $apiKey = $data['api_key'] ?? env('CLOUDINARY_API_KEY', env('CLOUDINARY_KEY', ''));
        $apiSecret = $data['api_secret'] ?? env('CLOUDINARY_API_SECRET', env('CLOUDINARY_SECRET', ''));

        $url = '';
        if ($cloudName && $apiKey && $apiSecret) {
            $url = sprintf('cloudinary://%s:%s@%s', $apiKey, $apiSecret, $cloudName);
        } elseif (env('CLOUDINARY_URL')) {
            $url = env('CLOUDINARY_URL');
        }

        return [
            'cloud_name' => $cloudName,
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'url' => $url,
        ];
    }

    /**
     * Apply Cloudinary config from database to Laravel's filesystems config.
     */
    public static function applyCloudinaryConfig(): void
    {
        try {
            $config = self::getCloudinaryConfig();
            if (empty($config['url'])) {
                return;
            }
            \Illuminate\Support\Facades\Config::set('filesystems.disks.cloudinary.url', $config['url']);
        } catch (\Throwable $e) {
            // Silently skip when Settings table may not exist (e.g. during migrate)
        }
    }

    /**
     * Apply SMTP config from database to Laravel's mail config.
     * When host is Resend SMTP (smtp.resend.com), use Resend HTTP API instead –
     * Railway blocks outbound SMTP on Free/Hobby plans.
     */
    public static function applySmtpConfig(): void
    {
        try {
            $config = self::getSmtpConfig();
            $host = strtolower($config['host'] ?? '');
            $resendHost = str_contains($host, 'resend.com');

            if ($resendHost) {
                // Resend via SMTP is blocked on Railway. Use Resend HTTP API instead.
                $apiKey = $config['password'] ?: env('RESEND_KEY');
                if ($apiKey) {
                    Config::set('mail.default', 'resend');
                    Config::set('mail.from.address', $config['from_address'] ?: env('MAIL_FROM_ADDRESS'));
                    Config::set('mail.from.name', $config['from_name'] ?: env('MAIL_FROM_NAME'));
                    Config::set('services.resend.key', $apiKey);
                    return;
                }
            }

            if (empty($config['host']) && empty(env('MAIL_HOST'))) {
                return;
            }

            Config::set('mail.default', $config['mailer']);
            Config::set('mail.from.address', $config['from_address']);
            Config::set('mail.from.name', $config['from_name']);
            Config::set('mail.mailers.smtp.host', $config['host']);
            Config::set('mail.mailers.smtp.port', $config['port']);
            Config::set('mail.mailers.smtp.username', $config['username']);
            Config::set('mail.mailers.smtp.password', $config['password']);
            Config::set('mail.mailers.smtp.encryption', $config['encryption'] ?: null);
        } catch (\Throwable $e) {
            // Silently skip when Settings table may not exist (e.g. during migrate)
        }
    }
}
