<?php

namespace App\Modules\User\Services\Admin;

use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Notifications\Driver\AccountBlockedNotification;
use App\Modules\Commerce\Notifications\Driver\AccountVerifiedNotification;
use App\Modules\Commerce\Notifications\Driver\OrderAssignedNotification;
use App\Modules\Commerce\Notifications\Driver\OrderCancelledNotification;
use App\Modules\Commerce\Notifications\Driver\OrderReassignedNotification;
use App\Modules\Commerce\Events\DriverNotificationBroadcast;
use App\Modules\Commerce\Events\OrderStatusUpdated;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\User\Models\DriverLocation;
use App\Modules\User\Models\User;
use App\Modules\Commerce\Services\Driver\DriverStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverManagementService
{
    public function listDrivers(Request $request)
    {
        $query = User::query()->whereHas('driver')->with('driver');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_verified')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('is_verified', $request->boolean('is_verified'));
            });
        }

        if ($request->has('is_online')) {
            $query->whereHas('driver', function ($q) use ($request) {
                $q->where('is_online', $request->boolean('is_online'));
            });
        }

        if ($status = $request->input('status')) {
            $normalized = match (strtolower($status)) {
                'new' => \App\Modules\User\Enums\UserStatusEnum::NEW->value,
                'active' => \App\Modules\User\Enums\UserStatusEnum::ACTIVE->value,
                'suspended' => \App\Modules\User\Enums\UserStatusEnum::SUSPENDED->value,
                'blocked' => \App\Modules\User\Enums\UserStatusEnum::BLOCKED->value,
                'inactive' => \App\Modules\User\Enums\UserStatusEnum::INACTIVE->value,
                default => $status,
            };
            $query->where('status', $normalized);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'name', 'email', 'status'];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $drivers = $query->paginate($perPage);

        $drivers->getCollection()->transform(function ($user) {
            return $this->mapDriver($user);
        });

        return $drivers;
    }

    public function getDriverDetails(string $id): array
    {
        $user = User::with(['driver', 'driverLocations'])->find($id);

        if (!$user || !$user->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        $latestLocation = $user->driverLocations()->latest('recorded_at')->first();

        return array_merge($this->mapDriver($user), [
            'latest_location' => $latestLocation ? [
                'lat' => $latestLocation->lat,
                'lng' => $latestLocation->lng,
                'bearing' => $latestLocation->bearing,
                'recorded_at' => $latestLocation->recorded_at,
            ] : null,
        ]);
    }

    public function getDriverStats(string $id): array
    {
        $user = User::with('driver')->find($id);

        if (!$user || !$user->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        $statsService = new DriverStatsService();
        $stats = $statsService->summary($user);

        return array_merge($this->mapDriver($user), [
            'stats' => $stats,
        ]);
    }

    public function verifyDriver(string $id, bool $approved, ?string $reason = null): User
    {
        $user = User::with('driver')->find($id);

        if (!$user || !$user->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        $user->driver->update([
            'is_verified' => $approved,
            'verified_at' => $approved ? now() : null,
            'is_online' => $approved ? $user->driver->is_online : false,
        ]);

        if ($approved) {
            $user->notify(new AccountVerifiedNotification($user));
        }

        return $user->fresh(['driver']);
    }

    public function blockDriver(string $id, ?string $reason = null): User
    {
        $user = User::with('driver')->find($id);

        if (!$user || !$user->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        $user->update([
            'status' => UserStatusEnum::BLOCKED,
        ]);

        $user->driver->update([
            'is_online' => false,
        ]);

        $user->notify(new AccountBlockedNotification($user, $reason));

        return $user->fresh(['driver']);
    }

    public function unblockDriver(string $id): User
    {
        $user = User::with('driver')->find($id);

        if (!$user || !$user->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        $user->update([
            'status' => UserStatusEnum::ACTIVE,
        ]);

        return $user->fresh(['driver']);
    }

    public function getLatestLocations(): array
    {
        // 1. Get drivers who have location data (latest per driver)
        $withLocation = DriverLocation::query()
            ->select('driver_locations.*')
            ->joinSub(
                DriverLocation::query()
                    ->select('user_id', DB::raw('MAX(recorded_at) as latest_recorded_at'))
                    ->groupBy('user_id'),
                'latest',
                function ($join) {
                    $join->on('driver_locations.user_id', '=', 'latest.user_id')
                        ->on('driver_locations.recorded_at', '=', 'latest.latest_recorded_at');
                }
            )
            ->with('user.driver')
            ->get();

        $driverIdsWithLocation = $withLocation->pluck('user_id')->flip();

        // 2. Get online drivers who have NO location data yet
        $onlineWithoutLocation = User::query()
            ->whereHas('driver', fn ($q) => $q->where('is_online', true))
            ->whereNotIn('id', $driverIdsWithLocation->keys())
            ->with('driver')
            ->get();

        $result = $withLocation->map(function ($location) {
            return [
                'driver_id' => $location->user_id,
                'lat' => $location->lat,
                'lng' => $location->lng,
                'bearing' => $location->bearing,
                'recorded_at' => $location->recorded_at,
                'driver' => $location->user ? [
                    'name' => $location->user->name,
                    'email' => $location->user->email,
                    'phone' => $location->user->phone,
                    'is_online' => $location->user->driver?->is_online,
                    'is_verified' => $location->user->driver?->is_verified,
                    'vehicle_type' => $location->user->driver?->vehicle_type,
                ] : null,
            ];
        })->toArray();

        // 3. Append online drivers without location (so fleet map list matches analytics)
        foreach ($onlineWithoutLocation as $user) {
            $result[] = [
                'driver_id' => $user->id,
                'lat' => null,
                'lng' => null,
                'bearing' => null,
                'recorded_at' => null,
                'driver' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'is_online' => $user->driver?->is_online,
                    'is_verified' => $user->driver?->is_verified,
                    'vehicle_type' => $user->driver?->vehicle_type,
                ],
            ];
        }

        return $result;
    }

    public function reassignOrder(string $orderId, string $driverId, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($orderId, $driverId, $reason) {
            $order = Order::with(['driver', 'vendor.user', 'user'])->lockForUpdate()->find($orderId);

            if (!$order) {
                throw new InvalidArgumentException('Order not found.');
            }

            if (in_array($order->status, ['CANCELLED', 'REFUNDED', 'COMPLETED', 'DELIVERED'])) {
                throw new InvalidArgumentException('Order cannot be reassigned.');
            }

            $newDriver = User::with('driver')->find($driverId);
            if (!$newDriver || !$newDriver->driver || !$newDriver->driver->is_verified) {
                throw new InvalidArgumentException('Driver is not eligible for reassignment.');
            }

            $previousDriver = $order->driver;

            $order->update([
                'driver_id' => $newDriver->id,
                'assigned_at' => now(),
            ]);

            if ($previousDriver) {
                $previousDriver->notify(new OrderCancelledNotification($order, $reason));
                event(new DriverNotificationBroadcast(
                    $previousDriver->id,
                    'order.cancelled',
                    [
                        'order_id' => $order->id,
                        'reason' => $reason,
                    ]
                ));
            }

            if ($previousDriver) {
                $newDriver->notify(new OrderReassignedNotification($order));
                event(new DriverNotificationBroadcast(
                    $newDriver->id,
                    'order.reassigned',
                    [
                        'order_id' => $order->id,
                    ]
                ));
            } else {
                $newDriver->notify(new OrderAssignedNotification($order));
                event(new DriverNotificationBroadcast(
                    $newDriver->id,
                    'order.assigned',
                    [
                        'order_id' => $order->id,
                    ]
                ));
            }

            event(new OrderStatusUpdated($order));

            return $order->fresh(['driver', 'vendor.user', 'user']);
        });
    }

    private function mapDriver(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'driver' => [
                'id' => $user->driver?->id,
                'vehicle_type' => $user->driver?->vehicle_type,
                'license_number' => $user->driver?->license_number,
                'is_verified' => $user->driver?->is_verified,
                'is_online' => $user->driver?->is_online,
                'verified_at' => $user->driver?->verified_at,
            ],
        ];
    }
}
