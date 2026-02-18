<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\DriverSupportTicket;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SosController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
            'order_id' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        try {
            $driver = User::find(Auth::id());

            $ticket = DriverSupportTicket::create([
                'driver_id' => $driver->id,
                'subject' => 'SOS Emergency',
                'message' => $data['reason'] ?? 'Driver triggered an SOS alert.',
                'priority' => 'HIGH',
                'status' => 'OPEN',
                'meta' => [
                    'order_id' => $data['order_id'] ?? null,
                    'location' => [
                        'latitude' => $data['latitude'] ?? null,
                        'longitude' => $data['longitude'] ?? null,
                    ],
                    'triggered_at' => now()->toISOString(),
                ],
            ]);

            Log::warning('DRIVER SOS ALERT', [
                'driver_id' => $driver->id,
                'ticket_id' => $ticket->id,
                'order_id' => $data['order_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
            ]);

            return ShopittPlus::response(true, 'SOS alert sent successfully', 201, $ticket);
        } catch (\Exception $e) {
            Log::error('DRIVER SOS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to send SOS alert', 500);
        }
    }
}
