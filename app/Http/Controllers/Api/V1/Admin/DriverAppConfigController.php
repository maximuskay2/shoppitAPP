<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Driver\DriverAppConfigRequest;
use App\Modules\Commerce\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DriverAppConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return ShopittPlus::response(true, 'Driver app config retrieved successfully', 200, [
            'force_update' => $this->getBool('driver_app_force_update'),
            'min_version' => Settings::getValue('driver_app_min_version'),
            'latest_version' => Settings::getValue('driver_app_latest_version'),
            'update_url' => Settings::getValue('driver_app_update_url'),
            'message' => Settings::getValue('driver_app_update_message'),
        ]);
    }

    public function update(DriverAppConfigRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $this->upsertSetting('driver_app_force_update',
                array_key_exists('force_update', $data) && $data['force_update'] ? 'true' : 'false',
                'Require driver app update before use'
            );
            $this->upsertSetting('driver_app_min_version', $data['min_version'] ?? null,
                'Minimum allowed driver app version'
            );
            $this->upsertSetting('driver_app_latest_version', $data['latest_version'] ?? null,
                'Latest driver app version'
            );
            $this->upsertSetting('driver_app_update_url', $data['update_url'] ?? null,
                'Driver app update URL'
            );
            $this->upsertSetting('driver_app_update_message', $data['message'] ?? null,
                'Driver app update message'
            );

            return $this->show();
        } catch (\Exception $e) {
            Log::error('DRIVER APP CONFIG UPDATE: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update driver app config', 500);
        }
    }

    private function getBool(string $name): bool
    {
        $value = Settings::getValue($name);
        if ($value === null) {
            return false;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function upsertSetting(string $name, ?string $value, string $description): void
    {
        if ($value === null) {
            return;
        }

        Settings::updateOrCreate(
            ['name' => $name],
            ['value' => $value, 'description' => $description]
        );
    }
}
