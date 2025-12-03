<?php

namespace App\Http\Controllers;

use App\Modules\Transaction\Services\PaystackService;
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
        try {
            $payload = $request->all();
            $signature = $request->header('x-paystack-signature');

            // Verify webhook signature
            if ($signature == null || !$this->paystackService->verifyWebhook($request->getContent(), $signature)) {
                Log::warning('Invalid Paystack webhook signature', ['signature',$signature]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }
            return response()->json();
            // Handle the webhook
            $this->paystackService->handleWebhook($payload);
            \Log::info('Paystack webhook handled successfully', ['headers' => $signature, 'payload' => $payload]);
            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Paystack webhook error', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['status' => 'error', 'message' => 'Internal server error'], 500);
        }
    }
}