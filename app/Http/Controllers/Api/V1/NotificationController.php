<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\Notification;

class NotificationController extends Controller
{
    // List notifications (paginated, unified structure)
    public function index(Request $request)
    {
        $user = Auth::user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'message' => 'Notifications fetched',
            'data' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'data' => $notifications->items(),
            ]
        ]);
    }

    // Mark as read
    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->update(['read_at' => Carbon::now()->toIso8601String()]);
        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    // Mark as unread
    public function markAsUnread(Request $request, $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->update(['read_at' => null]);
        return response()->json(['success' => true, 'message' => 'Notification marked as unread']);
    }

    // Send notification (unified payload, ISO8601 timestamps)
    public function send(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'required|array',
            'type' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $user = \App\Models\User::find($request->user_id);
        $notification = $user->notifications()->create([
            'type' => $request->type,
            'title' => $request->title,
            'body' => $request->body,
            'data' => $request->data,
            'created_at' => Carbon::now()->toIso8601String(),
            'updated_at' => Carbon::now()->toIso8601String(),
        ]);
        // Dispatch FCM push notification with retry logic
        $fcmNotification = new \App\Modules\User\Notifications\WelcomeOnboardNotification($request->title, $request->body, $request->data);
        $success = \App\Services\FcmNotificationService::sendWithRetry($user, $fcmNotification);
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Notification sent' : 'Notification failed after retries',
            'notification' => $notification
        ]);
    }
}
