<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryZoneController extends Controller
{
    public function index(): JsonResponse
    {
        // TODO: Replace with real DB fetch
        $zones = [
            ['id' => 1, 'name' => 'Zone A', 'radius_km' => 10, 'center' => [6.5244, 3.3792]],
            ['id' => 2, 'name' => 'Zone B', 'radius_km' => 15, 'center' => [6.4654, 3.4064]],
        ];
        return ShopittPlus::response(true, 'Delivery zones retrieved', 200, $zones);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'radius_km' => 'required|numeric|min:1',
            'center' => 'required|array',
            'center.0' => 'required|numeric',
            'center.1' => 'required|numeric',
        ]);
        // TODO: Save to DB
        Log::info('Delivery zone created', $data);
        return ShopittPlus::response(true, 'Delivery zone created', 201, $data);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'radius_km' => 'sometimes|numeric|min:1',
            'center' => 'sometimes|array',
            'center.0' => 'sometimes|numeric',
            'center.1' => 'sometimes|numeric',
        ]);
        // TODO: Update in DB
        Log::info('Delivery zone updated', ['id' => $id] + $data);
        return ShopittPlus::response(true, 'Delivery zone updated', 200, ['id' => $id] + $data);
    }

    public function destroy($id): JsonResponse
    {
        // TODO: Delete from DB
        Log::info('Delivery zone deleted', ['id' => $id]);
        return ShopittPlus::response(true, 'Delivery zone deleted', 200);
    }
}
