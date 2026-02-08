<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\User\Enums\UserStatusEnum;
use App\Modules\Commerce\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppConfigController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $status = $user?->status;
        $isBanned = $status instanceof UserStatusEnum
            ? $status !== UserStatusEnum::ACTIVE
            : false;

        $banReason = null;
        if ($isBanned) {
            $banReason = match ($status) {
                UserStatusEnum::SUSPENDED => 'Your account is suspended. Please contact support.',
                UserStatusEnum::BLOCKED => 'Your account is blocked. Please contact support.',
                default => 'Your account is restricted. Please contact support.',
            };
        }

        return ShopittPlus::response(true, 'Driver app config retrieved successfully', 200, [
            'force_update' => $this->getBool('driver_app_force_update', (bool) config('driver_app.force_update')),
            'min_version' => Settings::getValue('driver_app_min_version') ?? config('driver_app.min_version'),
            'latest_version' => Settings::getValue('driver_app_latest_version') ?? config('driver_app.latest_version'),
            'update_url' => Settings::getValue('driver_app_update_url') ?? config('driver_app.update_url'),
            'message' => Settings::getValue('driver_app_update_message') ?? config('driver_app.message'),
            'is_banned' => $isBanned,
            'ban_reason' => $banReason,
        ]);
    }

    private function getBool(string $name, bool $default): bool
    {
        $value = Settings::getValue($name);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
