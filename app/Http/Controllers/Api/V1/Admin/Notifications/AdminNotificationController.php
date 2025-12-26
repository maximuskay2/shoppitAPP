<?php

namespace App\Http\Controllers\v1\Admin\Notifications;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Notification::whereMorphedTo('notifiable', $user);

        if ($request->boolean('unread')) {
            $query->unread();
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%$search%")
                  ->orWhere('data', 'like', "%$search%");
            });
        }

        $perPage = (int) min($request->query('per_page', 20), 100);
        $items = $query->latest()->paginate($perPage);

        return TransactX::response(true, 'Notifications fetched successfully', 200, $items);
    }

    public function unreadCount(Request $request)
    {
        $count = Notification::whereMorphedTo('notifiable', $request->user())->unread()->count();
        return TransactX::response(true, 'Unread notifications count fetched', 200, ['unread' => $count]);
    }

    public function show(Request $request, string $id)
    {
        $notification = Notification::whereMorphedTo('notifiable', $request->user())->find($id);
        if (!$notification) {
            return TransactX::response(false, 'Notification not found', 404);
        }
        $notification->markAsRead();
        return TransactX::response(true, 'Notification fetched successfully', 200, $notification);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = Notification::whereMorphedTo('notifiable', $request->user())->find($id);
        if (!$notification) {
            return TransactX::response(false, 'Notification not found', 404);
        }
        $notification->markAsRead();
        return TransactX::response(true, 'Notification marked as read', 200);
    }

    public function markAllRead(Request $request)
    {
        $updated = Notification::whereMorphedTo('notifiable', $request->user())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return TransactX::response(true, 'All notifications marked as read', 200);
    }

    public function destroy(Request $request, string $id)
    {
        $deleted = Notification::whereMorphedTo('notifiable', $request->user())->where('id', $id)->delete();
        if (!$deleted) {
            return TransactX::response(false, 'Notification not found', 404);
        }
        return TransactX::response(true, 'Notification deleted successfully', 200);
    }

    public function destroyAll(Request $request)
    {
        $user = $request->user();
        Notification::whereMorphedTo('notifiable', $user)->delete();

        return TransactX::response(true, 'All notifications deleted successfully', 200,null);
    }
}