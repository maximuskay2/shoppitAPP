<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\CreateSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateSettingsRequest;
use App\Services\Admin\AdminSettingsService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminSettingsController extends Controller
{
    /**
     * Create a new AdminSettingsController instance.
     */
    public function __construct(
        private readonly AdminSettingsService $adminSettingsService
    ) {}

    /**
     * Get list of settings with advanced filtering and sorting
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $settings = $this->adminSettingsService->getSettings($request->all());

            return ShopittPlus::response(true, 'Settings retrieved successfully', 200, $settings);
        } catch (Exception $e) {
            Log::error('LIST SETTINGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve settings', 500);
        }
    }

    /**
     * Create a new setting
     */
    public function store(CreateSettingsRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $setting = $this->adminSettingsService->createSetting($validatedData);

            return ShopittPlus::response(true, 'Setting created successfully', 201, (object) ['setting' => $setting]);
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create setting', 500);
        }
    }

    /**
     * Get setting details
     */
    public function show(string $id): JsonResponse
    {
        try {
            $setting = $this->adminSettingsService->getSetting($id);

            return ShopittPlus::response(true, 'Setting details retrieved successfully', 200, (object) ['setting' => $setting]);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Setting not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('GET SETTING DETAILS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve setting details', 500);
        }
    }

    /**
     * Update setting
     */
    public function update(UpdateSettingsRequest $request, string $id): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $setting = $this->adminSettingsService->updateSetting($id, $validatedData);

            return ShopittPlus::response(true, 'Setting updated successfully', 200, (object) ['setting' => $setting]);
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update setting', 500);
        }
    }

    /**
     * Delete setting
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->adminSettingsService->deleteSetting($id);

            return ShopittPlus::response(true, 'Setting deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('DELETE SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete setting', 500);
        }
    }
}