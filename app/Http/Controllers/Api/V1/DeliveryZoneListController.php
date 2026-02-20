<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\DeliveryZone;

/**
 * Public endpoint to list active delivery zones.
 * Used at checkout so users can select the zone they want to order from.
 */
class DeliveryZoneListController extends Controller
{
    /**
     * GET /api/v1/delivery-zones
     * Returns list of active delivery zones.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        $zones = DeliveryZone::active()
            ->orderBy('name')
            ->get()
            ->map(fn (DeliveryZone $z) => [
                'id' => $z->id,
                'name' => $z->name,
                'description' => $z->description,
                'base_fee' => (float) $z->base_fee,
                'per_km_fee' => (float) $z->per_km_fee,
                'min_order_amount' => (float) ($z->min_order_amount ?? 0),
            ]);

        return ShopittPlus::response(true, 'Delivery zones retrieved', 200, $zones);
    }
}
