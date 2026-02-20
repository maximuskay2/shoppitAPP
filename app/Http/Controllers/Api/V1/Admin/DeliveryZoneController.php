<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\DeliveryZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryZoneController extends Controller
{
    public function index(): JsonResponse
    {
        $zones = DeliveryZone::orderBy('name')->get()->map(fn (DeliveryZone $z) => $this->formatZone($z));

        return ShopittPlus::response(true, 'Delivery zones retrieved', 200, $zones);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'areas' => 'nullable|array',
            'areas.*' => 'string|max:255',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1',
            'base_fee' => 'required|numeric|min:0',
            'per_km_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'estimated_time_min' => 'nullable|integer|min:1|max:480',
            'estimated_time_max' => 'nullable|integer|min:1|max:480',
            'is_active' => 'boolean',
        ]);

        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['estimated_time_min'] = $data['estimated_time_min'] ?? 30;
        $data['estimated_time_max'] = $data['estimated_time_max'] ?? 60;
        $data['is_active'] = $data['is_active'] ?? true;

        $zone = DeliveryZone::create($data);
        Log::info('Delivery zone created', ['id' => $zone->id]);

        return ShopittPlus::response(true, 'Delivery zone created', 201, $this->formatZone($zone->fresh()));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $zone = DeliveryZone::find($id);
        if (!$zone) {
            return ShopittPlus::response(false, 'Delivery zone not found', 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'areas' => 'nullable|array',
            'areas.*' => 'string|max:255',
            'center_latitude' => 'nullable|numeric|between:-90,90',
            'center_longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:0.1',
            'base_fee' => 'sometimes|numeric|min:0',
            'per_km_fee' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'estimated_time_min' => 'nullable|integer|min:1|max:480',
            'estimated_time_max' => 'nullable|integer|min:1|max:480',
            'is_active' => 'sometimes|boolean',
        ]);

        $zone->update($data);
        Log::info('Delivery zone updated', ['id' => $zone->id]);

        return ShopittPlus::response(true, 'Delivery zone updated', 200, $this->formatZone($zone->fresh()));
    }

    public function destroy(string $id): JsonResponse
    {
        $zone = DeliveryZone::find($id);
        if (!$zone) {
            return ShopittPlus::response(false, 'Delivery zone not found', 404);
        }

        $zone->delete();
        Log::info('Delivery zone deleted', ['id' => $id]);

        return ShopittPlus::response(true, 'Delivery zone deleted', 200);
    }

    private function formatZone(DeliveryZone $zone): array
    {
        return [
            'id' => $zone->id,
            'uuid' => $zone->id,
            'name' => $zone->name,
            'description' => $zone->description,
            'areas' => $zone->areas ?? [],
            'center_latitude' => $zone->center_latitude !== null ? (float) $zone->center_latitude : null,
            'center_longitude' => $zone->center_longitude !== null ? (float) $zone->center_longitude : null,
            'radius_km' => $zone->radius_km !== null ? (float) $zone->radius_km : null,
            'base_fee' => (float) $zone->base_fee,
            'per_km_fee' => (float) $zone->per_km_fee,
            'min_order_amount' => (float) ($zone->min_order_amount ?? 0),
            'estimated_time_min' => (int) ($zone->estimated_time_min ?? 30),
            'estimated_time_max' => (int) ($zone->estimated_time_max ?? 60),
            'is_active' => (bool) $zone->is_active,
            'created_at' => $zone->created_at?->toISOString(),
        ];
    }
}
