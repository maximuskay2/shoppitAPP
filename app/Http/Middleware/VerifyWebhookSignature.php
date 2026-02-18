<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyWebhookSignature
{
    /**
     * Supported payment providers
     */
    private const PROVIDER_PAYSTACK = 'paystack';
    private const PROVIDER_FLUTTERWAVE = 'flutterwave';
    private const PROVIDER_STRIPE = 'stripe';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $provider = null): Response
    {
        // Determine provider from route or parameter
        $provider = $provider ?? $this->detectProvider($request);

        if (!$provider) {
            Log::warning('Webhook received without identifiable provider', [
                'path' => $request->path(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Unknown webhook provider'], 400);
        }

        $isValid = match ($provider) {
            self::PROVIDER_PAYSTACK => $this->verifyPaystackSignature($request),
            self::PROVIDER_FLUTTERWAVE => $this->verifyFlutterwaveSignature($request),
            self::PROVIDER_STRIPE => $this->verifyStripeSignature($request),
            default => false,
        };

        if (!$isValid) {
            Log::warning('Invalid webhook signature', [
                'provider' => $provider,
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Log successful webhook
        Log::info('Webhook signature verified', [
            'provider' => $provider,
            'event' => $request->input('event') ?? $request->input('event_type') ?? 'unknown',
        ]);

        return $next($request);
    }

    /**
     * Detect provider from request path or headers
     */
    private function detectProvider(Request $request): ?string
    {
        $path = $request->path();

        if (str_contains($path, 'paystack')) {
            return self::PROVIDER_PAYSTACK;
        }

        if (str_contains($path, 'flutterwave') || str_contains($path, 'rave')) {
            return self::PROVIDER_FLUTTERWAVE;
        }

        if (str_contains($path, 'stripe')) {
            return self::PROVIDER_STRIPE;
        }

        // Check headers
        if ($request->hasHeader('x-paystack-signature')) {
            return self::PROVIDER_PAYSTACK;
        }

        if ($request->hasHeader('verif-hash')) {
            return self::PROVIDER_FLUTTERWAVE;
        }

        if ($request->hasHeader('stripe-signature')) {
            return self::PROVIDER_STRIPE;
        }

        return null;
    }

    /**
     * Verify Paystack webhook signature
     */
    private function verifyPaystackSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');
        $secretKey = config('services.paystack.secret_key');

        if (!$signature || !$secretKey) {
            return false;
        }

        $payload = $request->getContent();
        $computedSignature = hash_hmac('sha512', $payload, $secretKey);

        return hash_equals($signature, $computedSignature);
    }

    /**
     * Verify Flutterwave webhook signature
     */
    private function verifyFlutterwaveSignature(Request $request): bool
    {
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.secret_hash');

        if (!$signature || !$secretHash) {
            return false;
        }

        return hash_equals($secretHash, $signature);
    }

    /**
     * Verify Stripe webhook signature
     */
    private function verifyStripeSignature(Request $request): bool
    {
        $signature = $request->header('stripe-signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$signature || !$webhookSecret) {
            return false;
        }

        $payload = $request->getContent();

        try {
            // Parse Stripe signature header
            $signatureParts = [];
            foreach (explode(',', $signature) as $part) {
                [$key, $value] = explode('=', $part, 2);
                $signatureParts[$key] = $value;
            }

            if (!isset($signatureParts['t']) || !isset($signatureParts['v1'])) {
                return false;
            }

            $timestamp = $signatureParts['t'];
            $expectedSignature = $signatureParts['v1'];

            // Check timestamp tolerance (5 minutes)
            $tolerance = 300;
            if (abs(time() - $timestamp) > $tolerance) {
                Log::warning('Stripe webhook timestamp outside tolerance');
                return false;
            }

            // Compute expected signature
            $signedPayload = $timestamp . '.' . $payload;
            $computedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

            return hash_equals($expectedSignature, $computedSignature);
        } catch (\Exception $e) {
            Log::error('Error verifying Stripe signature', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get list of allowed IPs for webhooks (optional extra security)
     */
    public static function getAllowedIps(string $provider): array
    {
        return match ($provider) {
            self::PROVIDER_PAYSTACK => [
                '52.31.139.75',
                '52.49.173.169',
                '52.214.14.220',
            ],
            self::PROVIDER_FLUTTERWAVE => [
                // Flutterwave IPs - check their documentation for current list
            ],
            self::PROVIDER_STRIPE => [
                // Stripe uses a large range - use signature verification instead
            ],
            default => [],
        };
    }
}
