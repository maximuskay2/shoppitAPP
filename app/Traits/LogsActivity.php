<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Log a user activity
     *
     * @param string $action The action performed
     * @param string|null $description Additional description
     * @param Model|null $subject The model being acted upon
     * @param array $properties Additional properties to log
     */
    protected function logActivity(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = []
    ): void {
        $user = Auth::user();

        $logData = [
            'action' => $action,
            'description' => $description,
            'user_id' => $user?->id,
            'user_type' => $user?->role ?? 'guest',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'properties' => $properties,
        ];

        if ($subject) {
            $logData['subject_type'] = get_class($subject);
            $logData['subject_id'] = $subject->getKey();
        }

        // Log to Laravel logs
        Log::channel('activity')->info($action, $logData);

        // Optionally store in database for audit trail
        $this->storeActivityLog($logData);
    }

    /**
     * Store activity log in database
     */
    protected function storeActivityLog(array $data): void
    {
        try {
            \DB::table('activity_logs')->insert([
                'action' => $data['action'],
                'description' => $data['description'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'properties' => json_encode($data['properties']),
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent'],
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the application
            Log::error('Failed to store activity log', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Log authentication event
     */
    protected function logAuth(string $action, bool $success = true, array $extra = []): void
    {
        $this->logActivity(
            action: "auth.{$action}",
            description: $success ? "{$action} successful" : "{$action} failed",
            properties: array_merge([
                'success' => $success,
            ], $extra)
        );
    }

    /**
     * Log order event
     */
    protected function logOrderActivity(string $action, Model $order, array $extra = []): void
    {
        $this->logActivity(
            action: "order.{$action}",
            description: "Order {$action}",
            subject: $order,
            properties: array_merge([
                'order_number' => $order->order_number ?? $order->uuid,
            ], $extra)
        );
    }

    /**
     * Log payment event
     */
    protected function logPaymentActivity(string $action, ?Model $transaction = null, array $extra = []): void
    {
        $this->logActivity(
            action: "payment.{$action}",
            description: "Payment {$action}",
            subject: $transaction,
            properties: $extra
        );
    }

    /**
     * Log admin action
     */
    protected function logAdminAction(string $action, ?Model $subject = null, array $extra = []): void
    {
        $this->logActivity(
            action: "admin.{$action}",
            description: "Admin {$action}",
            subject: $subject,
            properties: array_merge([
                'admin_id' => Auth::id(),
            ], $extra)
        );
    }

    /**
     * Log security event
     */
    protected function logSecurityEvent(string $event, array $details = []): void
    {
        Log::channel('security')->warning($event, [
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log API request for monitoring
     */
    protected function logApiRequest(string $endpoint, int $statusCode, float $duration): void
    {
        Log::channel('api')->info('API Request', [
            'endpoint' => $endpoint,
            'method' => request()->method(),
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2),
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
        ]);
    }
}
