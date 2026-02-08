<?php

namespace App\Http\Middleware;

use App\Modules\Commerce\Models\Order;
use App\Modules\User\Models\Admin;
use App\Modules\User\Models\AuditLog;
use App\Modules\User\Models\DriverDocument;
use App\Modules\User\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName();
        $auditConfig = $this->resolveAuditConfig($routeName, $request);

        if ($auditConfig === null) {
            return $response;
        }

        $actor = $request->user('admin-api') ?? $request->user('admin');
        $actorId = $actor instanceof User ? $actor->id : null;

        AuditLog::create([
            'actor_id' => $actorId,
            'actor_type' => $actor ? Admin::class : null,
            'action' => $auditConfig['action'],
            'auditable_type' => $auditConfig['auditable_type'],
            'auditable_id' => $auditConfig['auditable_id'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'meta' => $auditConfig['meta'],
        ]);

        return $response;
    }

    private function resolveAuditConfig(?string $routeName, Request $request): ?array
    {
        if (!$routeName) {
            return null;
        }

        $id = (string) $request->route('id');
        if ($id === '') {
            return null;
        }
        $meta = [];

        switch ($routeName) {
            case 'admin.drivers.verify':
                $meta = [
                    'approved' => $request->boolean('approved'),
                    'reason' => $request->input('reason'),
                ];
                return $this->buildAudit('driver.verify', User::class, $id, $meta);
            case 'admin.drivers.block':
                return $this->buildAudit('driver.block', User::class, $id, $meta);
            case 'admin.drivers.unblock':
                return $this->buildAudit('driver.unblock', User::class, $id, $meta);
            case 'admin.drivers.documents.approve':
                return $this->buildAudit('driver.document.approve', DriverDocument::class, $id, $meta);
            case 'admin.drivers.documents.reject':
                $meta = [
                    'reason' => $request->input('reason'),
                ];
                return $this->buildAudit('driver.document.reject', DriverDocument::class, $id, $meta);
            case 'admin.users.update':
                $meta = $request->only([
                    'name',
                    'email',
                    'phone',
                    'address',
                    'city',
                    'state',
                    'country',
                    'status',
                ]);
                return $this->buildAudit('user.update', User::class, $id, $meta);
            case 'admin.orders.reassign':
                $meta = [
                    'new_driver_id' => $request->input('driver_id'),
                    'reason' => $request->input('reason'),
                ];
                return $this->buildAudit('order.reassign', Order::class, $id, $meta);
            case 'admin.orders.update.status':
                $meta = [
                    'status' => $request->input('status'),
                    'note' => $request->input('note'),
                ];
                return $this->buildAudit('order.update_status', Order::class, $id, $meta);
            case 'admin.payouts.approve':
                $meta = [
                    'reference' => $request->input('reference'),
                ];
                return $this->buildAudit('payout.approve', User::class, $id, $meta);
            default:
                return null;
        }
    }

    private function buildAudit(string $action, string $type, ?string $id, array $meta): array
    {
        return [
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'meta' => $meta ?: null,
        ];
    }
}
