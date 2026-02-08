<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Driver\DriverPodUploadRequest;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderDeliveryProof;
use App\Modules\User\Models\User;
use App\Modules\User\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OrderProofController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}

    public function store(DriverPodUploadRequest $request, string $orderId): JsonResponse
    {
        try {
            $driver = User::find(Auth::id());

            if (!$driver) {
                return ShopittPlus::response(false, 'Driver not found', 404);
            }

            $order = Order::where('id', $orderId)
                ->where('driver_id', $driver->id)
                ->first();

            if (!$order) {
                return ShopittPlus::response(false, 'Order not found for this driver', 404);
            }

            $photoFile = $request->file('photo');
            $signatureFile = $request->file('signature');

            if (!$photoFile && !$signatureFile) {
                return ShopittPlus::response(false, 'Provide a photo or signature', 422, [
                    'photo' => ['A proof photo or signature is required.'],
                ]);
            }

            $existingProof = OrderDeliveryProof::where('order_id', $order->id)->first();
            $photoUrl = $existingProof?->photo_url;
            $signatureUrl = $existingProof?->signature_url;
            $meta = $existingProof?->meta ?? [];

            if ($photoFile) {
                $upload = $this->cloudinaryService->uploadDeliveryProof($photoFile, $driver->id, 'photo');
                if (!$upload['success']) {
                    return ShopittPlus::response(false, $upload['message'] ?? 'Photo upload failed', 500);
                }
                $photoUrl = $upload['data']['secure_url'] ?? $upload['data']['url'] ?? '';
                $meta['photo'] = $upload['data'] ?? null;
            }

            if ($signatureFile) {
                $upload = $this->cloudinaryService->uploadDeliveryProof($signatureFile, $driver->id, 'signature');
                if (!$upload['success']) {
                    return ShopittPlus::response(false, $upload['message'] ?? 'Signature upload failed', 500);
                }
                $signatureUrl = $upload['data']['secure_url'] ?? $upload['data']['url'] ?? '';
                $meta['signature'] = $upload['data'] ?? null;
            }

            $proof = OrderDeliveryProof::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'driver_id' => $driver->id,
                    'photo_url' => $photoUrl,
                    'signature_url' => $signatureUrl,
                    'meta' => $meta,
                ]
            );

            return ShopittPlus::response(true, 'Proof of delivery uploaded', 201, $proof);
        } catch (InvalidArgumentException $e) {
            Log::error('DRIVER POD UPLOAD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (\Exception $e) {
            Log::error('DRIVER POD UPLOAD: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to upload proof of delivery', 500);
        }
    }
}
