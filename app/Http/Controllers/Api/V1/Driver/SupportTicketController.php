<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverSupportTicketRequest;
use App\Modules\User\Models\DriverSupportTicket;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $perPage = min((int) $request->input('per_page', 20), 100);

            $query = DriverSupportTicket::where('driver_id', $driver->id)->latest();

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            $tickets = $query->cursorPaginate($perPage);

            $data = [
                'data' => $tickets->items(),
                'next_cursor' => $tickets->nextCursor()?->encode(),
                'prev_cursor' => $tickets->previousCursor()?->encode(),
                'has_more' => $tickets->hasMorePages(),
                'per_page' => $tickets->perPage(),
            ];

            return ShopittPlus::response(true, 'Support tickets retrieved successfully', 200, $data);
        } catch (\Exception $e) {
            Log::error('DRIVER SUPPORT TICKETS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve support tickets', 500);
        }
    }

    public function store(DriverSupportTicketRequest $request): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());
            $data = $request->validated();

            $ticket = DriverSupportTicket::create([
                'driver_id' => $driver->id,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'priority' => $data['priority'] ?? 'NORMAL',
                'status' => 'OPEN',
                'meta' => $data['meta'] ?? null,
            ]);

            return ShopittPlus::response(true, 'Support ticket created successfully', 201, $ticket);
        } catch (\Exception $e) {
            Log::error('DRIVER SUPPORT TICKETS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create support ticket', 500);
        }
    }
}
