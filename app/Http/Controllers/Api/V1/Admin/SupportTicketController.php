<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Messaging\Models\Conversation;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\DriverSupportTicket;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SupportTicketController extends Controller
{
    public function __construct(private readonly MessagingService $messagingService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $query = DriverSupportTicket::with('driver')->latest();

            if ($status = $request->input('status')) {
                $query->where('status', $status);
            }

            if ($priority = $request->input('priority')) {
                $query->where('priority', $priority);
            }

            if ($driverId = $request->input('driver_id')) {
                $query->where('driver_id', $driverId);
            }

            if ($search = $request->input('search')) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('subject', 'LIKE', "%{$search}%")
                        ->orWhere('message', 'LIKE', "%{$search}%");
                });
            }

            $perPage = min((int) $request->input('per_page', 20), 100);
            $tickets = $query->paginate($perPage);

            return ShopittPlus::response(true, 'Support tickets retrieved successfully', 200, $tickets);
        } catch (\Exception $e) {
            Log::error('ADMIN SUPPORT TICKETS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve support tickets', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $ticket = DriverSupportTicket::with('driver')->findOrFail($id);

            return ShopittPlus::response(true, 'Support ticket retrieved successfully', 200, $ticket);
        } catch (\Exception $e) {
            Log::error('ADMIN SUPPORT TICKET SHOW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve support ticket', 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:OPEN,IN_PROGRESS,RESOLVED,CLOSED'],
            'priority' => ['nullable', 'string', 'in:LOW,NORMAL,HIGH'],
            'response' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $ticket = DriverSupportTicket::findOrFail($id);

            if (isset($data['status'])) {
                $ticket->status = $data['status'];
                $ticket->resolved_at = $data['status'] === 'RESOLVED' ? now() : null;
            }

            if (isset($data['priority'])) {
                $ticket->priority = $data['priority'];
            }

            if (!empty($data['response'])) {
                $meta = $ticket->meta ?? [];
                $meta['admin_response'] = $data['response'];
                $meta['admin_response_by'] = Auth::guard('admin-api')->id();
                $meta['admin_response_at'] = now()->toISOString();

                // Send admin response to driver's messaging (so it appears in rider app)
                $admin = Auth::guard('admin-api')->user();
                $driver = User::find($ticket->driver_id);
                if ($admin instanceof Admin && $driver && $driver->driver) {
                    $conversation = null;
                    if (!empty($meta['conversation_id'])) {
                        $conversation = Conversation::find($meta['conversation_id']);
                    }
                    if (!$conversation) {
                        $conversation = $this->messagingService->getOrCreateConversation(
                            Conversation::TYPE_ADMIN_DRIVER,
                            $admin,
                            $driver,
                            'driver',
                            null
                        );
                        $meta['conversation_id'] = $conversation->id;
                    }
                    $this->messagingService->sendMessage($conversation, $admin, $data['response']);
                }
                $ticket->meta = $meta;
            }

            $ticket->save();

            return ShopittPlus::response(true, 'Support ticket updated successfully', 200, $ticket);
        } catch (\Exception $e) {
            Log::error('ADMIN SUPPORT TICKET UPDATE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update support ticket', 500);
        }
    }
}
