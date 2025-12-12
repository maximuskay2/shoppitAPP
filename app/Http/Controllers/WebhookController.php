<?php

namespace App\Http\Controllers;

use App\Modules\Transaction\Jobs\ProcessPaystackWebhook;
use App\Modules\Transaction\Services\External\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    public function handlePaystackWebhook(Request $request)
    {
        $secretKey = config('services.paystack.mode') === 'live' ? config('services.paystack.live_secret_key') : config('services.paystack.test_secret_key');
        try {
            $payload = $request->all();
            $ipAddress = $request->ip();

            $signature = $request->header('x-paystack-signature');
            $computedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

            // Verify webhook signature
            if ($signature == null || !hash_equals($computedSignature, $signature)) {
                Log::warning('Invalid Paystack webhook signature', ['signature',$signature]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }

            Log::info('Paystack webhook handled successfully', ['payload' => $payload]);
            
            // Dispatch to queue
            ProcessPaystackWebhook::dispatch($payload, $ipAddress);

            return response()->json(['message' => 'Webhook queued for processing'], 200);
        } catch (\Exception $e) {
            Log::error('Paystack webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }
}