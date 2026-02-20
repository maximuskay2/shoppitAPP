<?php

namespace App\Modules\User\Services;

use App\Helpers\RuntimeConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EbulkSmsService
{
    public function sendOtp(string $phone, string $message, int $flash = 0, int $dndsender = 0): bool
    {
        $config = RuntimeConfig::getEbulksmsConfig();
        $username = $config['username'] ?? null;
        $apiKey = $config['api_key'] ?? null;
        $sender = $config['sender'] ?? null;
        $baseUrl = $config['base_url'] ?? 'https://api.ebulksms.com/sendsms.json';
        $countryCode = $config['country_code'] ?? '234';

        if (!$username || !$apiKey || !$sender) {
            throw new InvalidArgumentException('SMS service is not configured.');
        }

        $normalized = $this->normalizePhone($phone, $countryCode);
        $payload = [
            'SMS' => [
                'auth' => [
                    'username' => $username,
                    'apikey' => $apiKey,
                ],
                'message' => [
                    'sender' => substr($sender, 0, 11),
                    'messagetext' => $message,
                    'flash' => (string) $flash,
                ],
                'recipients' => [
                    'gsm' => [
                        [
                            'msidn' => $normalized,
                            'msgid' => substr(uniqid('otp_', false), 0, 30),
                        ],
                    ],
                ],
                'dndsender' => $dndsender,
            ],
        ];

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->post($baseUrl, $payload);

        if (!$response->ok()) {
            Log::error('EbulkSMS request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        }

        $data = $response->json();
        $status = data_get($data, 'response.status') ?? data_get($data, 'status');

        if (is_string($status) && stripos($status, 'SUCCESS') !== false) {
            return true;
        }

        Log::error('EbulkSMS returned failure', [
            'response' => $data,
        ]);

        return false;
    }

    private function normalizePhone(string $phone, string $countryCode): string
    {
        $trimmed = trim($phone);

        if (str_starts_with($trimmed, '+')) {
            return substr($trimmed, 1);
        }

        if (str_starts_with($trimmed, '0')) {
            return $countryCode . substr($trimmed, 1);
        }

        return $trimmed;
    }
}
