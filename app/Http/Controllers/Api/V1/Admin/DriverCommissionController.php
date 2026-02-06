<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Driver\CommissionSettingRequest;
use App\Modules\Commerce\Models\DeliveryRadius;
use App\Modules\Commerce\Models\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DriverCommissionController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $value = Settings::getValue('driver_commission_rate');
            $deliveryFeeCommission = Settings::getValue('delivery_fee_commission');
            $minimumWithdrawal = Settings::getValue('minimum_withdrawal');
            $radius = DeliveryRadius::where('name', 'default')->first();

            return ShopittPlus::response(true, 'Commission setting retrieved successfully', 200, [
                'driver_commission_rate' => $value !== null ? (float) $value : null,
                'delivery_fee_commission' => $deliveryFeeCommission !== null ? (float) $deliveryFeeCommission : null,
                'minimum_withdrawal' => $minimumWithdrawal !== null ? (float) $minimumWithdrawal : null,
                'driver_match_radius_km' => $radius ? (float) $radius->radius_km : null,
                'driver_match_radius_active' => $radius ? (bool) $radius->is_active : null,
            ]);
        } catch (\Exception $e) {
            Log::error('GET COMMISSION SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve commission setting', 500);
        }
    }

    public function update(CommissionSettingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $commissionRate = $data['driver_commission_rate'] ?? null;
            $deliveryFeeCommission = $data['delivery_fee_commission'] ?? null;
            $minimumWithdrawal = $data['minimum_withdrawal'] ?? null;
            $radiusKm = $data['driver_match_radius_km'] ?? null;
            $radiusActive = $data['driver_match_radius_active'] ?? null;

            if (
                $commissionRate === null &&
                $deliveryFeeCommission === null &&
                $minimumWithdrawal === null &&
                $radiusKm === null &&
                $radiusActive === null
            ) {
                return ShopittPlus::response(false, 'Provide at least one setting to update.', 422);
            }

            $setting = null;
            if ($commissionRate !== null) {
                $setting = Settings::updateOrCreate(
                    ['name' => 'driver_commission_rate'],
                    ['value' => $commissionRate, 'description' => 'Driver commission rate (%)']
                );
            }

            if ($deliveryFeeCommission !== null) {
                Settings::updateOrCreate(
                    ['name' => 'delivery_fee_commission'],
                    ['value' => $deliveryFeeCommission, 'description' => 'Delivery fee commission (%)']
                );
            }

            if ($minimumWithdrawal !== null) {
                Settings::updateOrCreate(
                    ['name' => 'minimum_withdrawal'],
                    ['value' => $minimumWithdrawal, 'description' => 'Minimum withdrawal amount']
                );
            }

            if ($radiusKm !== null || $radiusActive !== null) {
                $radiusPayload = [
                    'description' => 'Default delivery radius in kilometers',
                ];
                if ($radiusKm !== null) {
                    $radiusPayload['radius_km'] = $radiusKm;
                }
                if ($radiusActive !== null) {
                    $radiusPayload['is_active'] = (bool) $radiusActive;
                }

                DeliveryRadius::updateOrCreate(
                    ['name' => 'default'],
                    $radiusPayload
                );
            }

            $activeRadius = DeliveryRadius::where('name', 'default')->first();

            return ShopittPlus::response(true, 'Commission setting updated successfully', 200, [
                'driver_commission_rate' => $setting ? (float) $setting->value : ($commissionRate !== null ? (float) $commissionRate : null),
                'delivery_fee_commission' => $deliveryFeeCommission !== null ? (float) $deliveryFeeCommission : (Settings::getValue('delivery_fee_commission') !== null ? (float) Settings::getValue('delivery_fee_commission') : null),
                'minimum_withdrawal' => $minimumWithdrawal !== null ? (float) $minimumWithdrawal : (Settings::getValue('minimum_withdrawal') !== null ? (float) Settings::getValue('minimum_withdrawal') : null),
                'driver_match_radius_km' => $activeRadius ? (float) $activeRadius->radius_km : ($radiusKm !== null ? (float) $radiusKm : null),
                'driver_match_radius_active' => $activeRadius ? (bool) $activeRadius->is_active : ($radiusActive !== null ? (bool) $radiusActive : null),
            ]);
        } catch (\Exception $e) {
            Log::error('UPDATE COMMISSION SETTING: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update commission setting', 500);
        }
    }
}
