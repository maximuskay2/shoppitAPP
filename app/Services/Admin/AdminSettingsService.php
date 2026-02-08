<?php

namespace App\Services\Admin;

use App\Modules\Commerce\Models\Settings;
use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminSettingsService
{
    /**
     * Get all settings with filters
     */
    public function getSettings(array $filters = []): mixed
    {
        try {
            $query = Settings::query()->orderBy('name');

            // Apply search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                      ->orWhere('description', 'LIKE', '%' . $search . '%')
                      ->orWhere('value', 'LIKE', '%' . $search . '%');
                });
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            $settings = $query->paginate($perPage);

            $formattedSettings = $settings->getCollection()->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'name' => $setting->name,
                    'value' => $setting->value,
                    'description' => $setting->description,
                    'created_at' => $setting->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $setting->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            $settings->setCollection($formattedSettings);

            return $settings;
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - GET SETTINGS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single setting
     */
    public function getSetting(string $id): array
    {
        try {
            $setting = Settings::find($id);

            if (!$setting) {
                throw new Exception('Setting not found');
            }

            return [
                'id' => $setting->id,
                'name' => $setting->name,
                'value' => $setting->value,
                'description' => $setting->description,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $setting->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - GET SETTING: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new setting
     */
    public function createSetting(array $data): Settings
    {
        try {
            // Check if setting with name already exists
            if (Settings::where('name', $data['name'])->exists()) {
                throw new InvalidArgumentException('Setting with this name already exists');
            }

            $setting = Settings::create([
                'name' => $data['name'],
                'value' => $data['value'],
                'description' => $data['description'] ?? null,
            ]);

            return $setting;
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - CREATE SETTING: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a setting
     */
    public function updateSetting(string $id, array $data): Settings
    {
        try {
            $setting = Settings::find($id);

            if (!$setting) {
                throw new InvalidArgumentException('Setting not found');
            }

            // Check name uniqueness if changed
            if (isset($data['name']) && $data['name'] !== $setting->name) {
                if (Settings::where('name', $data['name'])->where('id', '!=', $id)->exists()) {
                    throw new InvalidArgumentException('Setting with this name already exists');
                }
            }

            $setting->update([
                'name' => $data['name'] ?? $setting->name,
                'value' => $data['value'] ?? $setting->value,
                'description' => $data['description'] ?? $setting->description,
            ]);

            return $setting->fresh();
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - UPDATE SETTING: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a setting
     */
    public function deleteSetting(string $id): void
    {
        try {
            $setting = Settings::find($id);

            if (!$setting) {
                throw new InvalidArgumentException('Setting not found');
            }

            $setting->delete();
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - DELETE SETTING: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get setting by name
     */
    public function getSettingByName(string $name): ?array
    {
        try {
            $setting = Settings::where('name', $name)->first();

            if (!$setting) {
                return null;
            }

            return [
                'id' => $setting->id,
                'name' => $setting->name,
                'value' => $setting->value,
                'description' => $setting->description,
                'created_at' => $setting->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $setting->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            Log::error('ADMIN SETTINGS SERVICE - GET SETTING BY NAME: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
