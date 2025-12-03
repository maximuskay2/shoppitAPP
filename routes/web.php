<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-payment', function () {
    return view('test');
});

Route::post('/api/verify-payment', function (Request $request) {
    $reference = $request->input('reference');
    $secretKey = config('services.paystack.secret_key');

    try {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
        ])->get("https://api.paystack.co/transaction/verify/{$reference}");

        if ($response->successful()) {
            $data = $response->json();
            return response()->json([
                'success' => true,
                'message' => 'Payment verified successfully',
                'data' => $data['data']
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed',
                'data' => $response->json()
            ], 400);
        }
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error verifying payment: ' . $e->getMessage()
        ], 500);
    }
});

Route::post('/webhooks/paystack', [WebhookController::class, 'handlePaystackWebhook']);
