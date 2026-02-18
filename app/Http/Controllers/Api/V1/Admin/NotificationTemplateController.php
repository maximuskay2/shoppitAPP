<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\NotificationTemplate;
use App\Modules\Commerce\Models\ScheduledNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationTemplateController extends Controller
{
    /**
     * List all notification templates
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = NotificationTemplate::query();

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $templates = $query->orderBy('created_at', 'desc')->paginate(20);

            return ShopittPlus::response(true, 'Templates retrieved successfully', 200, $templates);
        } catch (\Exception $e) {
            Log::error('NOTIFICATION TEMPLATES INDEX: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve templates', 500);
        }
    }

    /**
     * Create a new notification template
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|unique:notification_templates,name',
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'type' => 'required|in:push,email,sms',
                'category' => 'nullable|string',
                'variables' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            $template = NotificationTemplate::create([
                'name' => $request->name,
                'title' => $request->title,
                'body' => $request->body,
                'type' => $request->type,
                'category' => $request->category,
                'variables' => $request->variables,
                'is_active' => $request->boolean('is_active', true),
            ]);

            return ShopittPlus::response(true, 'Template created successfully', 201, $template);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ShopittPlus::response(false, $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('NOTIFICATION TEMPLATE CREATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create template', 500);
        }
    }

    /**
     * Show a specific template
     */
    public function show(string $id): JsonResponse
    {
        try {
            $template = NotificationTemplate::where('uuid', $id)->firstOrFail();
            return ShopittPlus::response(true, 'Template retrieved successfully', 200, $template);
        } catch (\Exception $e) {
            return ShopittPlus::response(false, 'Template not found', 404);
        }
    }

    /**
     * Update a notification template
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $template = NotificationTemplate::where('uuid', $id)->firstOrFail();

            $request->validate([
                'name' => 'string|unique:notification_templates,name,' . $template->id,
                'title' => 'string|max:255',
                'body' => 'string',
                'type' => 'in:push,email,sms',
                'category' => 'nullable|string',
                'variables' => 'nullable|array',
                'is_active' => 'boolean',
            ]);

            $template->update($request->only([
                'name', 'title', 'body', 'type', 'category', 'variables', 'is_active'
            ]));

            return ShopittPlus::response(true, 'Template updated successfully', 200, $template);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ShopittPlus::response(false, $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('NOTIFICATION TEMPLATE UPDATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update template', 500);
        }
    }

    /**
     * Delete a notification template
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $template = NotificationTemplate::where('uuid', $id)->firstOrFail();
            $template->delete();
            return ShopittPlus::response(true, 'Template deleted successfully', 200);
        } catch (\Exception $e) {
            Log::error('NOTIFICATION TEMPLATE DELETE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete template', 500);
        }
    }

    /**
     * List scheduled notifications
     */
    public function scheduledIndex(Request $request): JsonResponse
    {
        try {
            $query = ScheduledNotification::with('template');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $notifications = $query->orderBy('scheduled_at', 'desc')->paginate(20);

            return ShopittPlus::response(true, 'Scheduled notifications retrieved', 200, $notifications);
        } catch (\Exception $e) {
            Log::error('SCHEDULED NOTIFICATIONS INDEX: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve scheduled notifications', 500);
        }
    }

    /**
     * Schedule a notification
     */
    public function schedule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'template_id' => 'nullable|exists:notification_templates,uuid',
                'title' => 'required_without:template_id|string|max:255',
                'body' => 'required_without:template_id|string',
                'type' => 'required|in:push,email,sms',
                'target_audience' => 'required|in:all,customers,vendors,drivers',
                'target_user_ids' => 'nullable|array',
                'scheduled_at' => 'required|date|after:now',
            ]);

            $scheduled = ScheduledNotification::create([
                'template_id' => $request->template_id ? NotificationTemplate::where('uuid', $request->template_id)->first()?->id : null,
                'title' => $request->title ?? '',
                'body' => $request->body ?? '',
                'type' => $request->type,
                'target_audience' => $request->target_audience,
                'target_user_ids' => $request->target_user_ids,
                'scheduled_at' => $request->scheduled_at,
                'status' => 'pending',
            ]);

            return ShopittPlus::response(true, 'Notification scheduled successfully', 201, $scheduled);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ShopittPlus::response(false, $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('SCHEDULE NOTIFICATION: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to schedule notification', 500);
        }
    }

    /**
     * Cancel a scheduled notification
     */
    public function cancelScheduled(string $id): JsonResponse
    {
        try {
            $scheduled = ScheduledNotification::where('uuid', $id)->firstOrFail();
            
            if (!$scheduled->isPending()) {
                return ShopittPlus::response(false, 'Only pending notifications can be cancelled', 400);
            }

            $scheduled->cancel();
            return ShopittPlus::response(true, 'Scheduled notification cancelled', 200);
        } catch (\Exception $e) {
            Log::error('CANCEL SCHEDULED NOTIFICATION: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to cancel scheduled notification', 500);
        }
    }
}
