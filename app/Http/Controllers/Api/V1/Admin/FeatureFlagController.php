<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\FeatureFlag;
use App\Modules\Commerce\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeatureFlagController extends Controller
{
    /**
     * List all feature flags
     */
    public function index(): JsonResponse
    {
        try {
            $flags = FeatureFlag::orderBy('key')->get();
            return ShopittPlus::response(true, 'Feature flags retrieved', 200, $flags);
        } catch (\Exception $e) {
            Log::error('FEATURE FLAGS INDEX: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve feature flags', 500);
        }
    }

    /**
     * Create a new feature flag
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'key' => 'required|string|unique:feature_flags,key',
                'name' => 'required|string',
                'description' => 'nullable|string',
                'is_enabled' => 'boolean',
                'conditions' => 'nullable|array',
                'environment' => 'in:all,production,staging,development',
            ]);

            $flag = FeatureFlag::create([
                'key' => $request->key,
                'name' => $request->name,
                'description' => $request->description,
                'is_enabled' => $request->boolean('is_enabled', false),
                'conditions' => $request->conditions,
                'environment' => $request->environment ?? 'all',
            ]);

            return ShopittPlus::response(true, 'Feature flag created', 201, $flag);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ShopittPlus::response(false, $e->errors(), 422);
        } catch (\Exception $e) {
            Log::error('FEATURE FLAG CREATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create feature flag', 500);
        }
    }

    /**
     * Update a feature flag
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $flag = FeatureFlag::where('uuid', $id)->firstOrFail();

            $request->validate([
                'name' => 'string',
                'description' => 'nullable|string',
                'is_enabled' => 'boolean',
                'conditions' => 'nullable|array',
                'environment' => 'in:all,production,staging,development',
            ]);

            $flag->update($request->only([
                'name', 'description', 'is_enabled', 'conditions', 'environment'
            ]));

            return ShopittPlus::response(true, 'Feature flag updated', 200, $flag);
        } catch (\Exception $e) {
            Log::error('FEATURE FLAG UPDATE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update feature flag', 500);
        }
    }

    /**
     * Toggle a feature flag
     */
    public function toggle(string $id): JsonResponse
    {
        try {
            $flag = FeatureFlag::where('uuid', $id)->firstOrFail();
            $flag->update(['is_enabled' => !$flag->is_enabled]);
            return ShopittPlus::response(true, 'Feature flag toggled', 200, $flag);
        } catch (\Exception $e) {
            Log::error('FEATURE FLAG TOGGLE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to toggle feature flag', 500);
        }
    }

    /**
     * Delete a feature flag
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $flag = FeatureFlag::where('uuid', $id)->firstOrFail();
            $flag->delete();
            return ShopittPlus::response(true, 'Feature flag deleted', 200);
        } catch (\Exception $e) {
            Log::error('FEATURE FLAG DELETE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete feature flag', 500);
        }
    }

    /**
     * Get maintenance mode status
     */
    public function maintenanceStatus(): JsonResponse
    {
        try {
            $isEnabled = SystemSetting::isMaintenanceMode();
            $message = SystemSetting::getValue('maintenance_message', 'System is under maintenance');
            
            return ShopittPlus::response(true, 'Maintenance status retrieved', 200, [
                'is_enabled' => $isEnabled,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('MAINTENANCE STATUS: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to get maintenance status', 500);
        }
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenance(Request $request): JsonResponse
    {
        try {
            $currentStatus = SystemSetting::isMaintenanceMode();
            $newStatus = !$currentStatus;

            SystemSetting::setValue('maintenance_mode', $newStatus, 'boolean', 'System maintenance mode');
            
            if ($request->has('message')) {
                SystemSetting::setValue('maintenance_message', $request->message, 'string', 'Maintenance mode message');
            }

            return ShopittPlus::response(true, 'Maintenance mode ' . ($newStatus ? 'enabled' : 'disabled'), 200, [
                'is_enabled' => $newStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('TOGGLE MAINTENANCE: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to toggle maintenance mode', 500);
        }
    }
}
