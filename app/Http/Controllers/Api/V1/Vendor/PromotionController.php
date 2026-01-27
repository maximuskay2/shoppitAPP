<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Vendor\RequestPromotionRequest;
use App\Http\Resources\Commerce\PromotionResource;
use App\Modules\Commerce\Services\PromotionService;
use App\Modules\User\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotionService) {}

    /**
     * List available promotions
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'type' => $request->query('type'), // active, scheduled, expired
                'per_page' => $request->query('per_page', 15),
            ];

            $promotions = $this->promotionService->listAvailablePromotions($filters);

            return ShopittPlus::response(true, 'Promotions retrieved successfully', 200, PromotionResource::collection($promotions)->response()->getData());
        } catch (Exception $e) {
            Log::error('LIST AVAILABLE PROMOTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve promotions', 500);
        }
    }

    /**
     * Get a single promotion
     */
    public function show(string $id): JsonResponse
    {
        try {
            $promotion = $this->promotionService->getPromotion($id);

            return ShopittPlus::response(true, 'Promotion retrieved successfully', 200, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('GET PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('GET PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve promotion', 500);
        }
    }

    /**
     * Request a promotion (vendor)
     */
    public function requestPromotion(RequestPromotionRequest $request): JsonResponse
    {
        try {
            $user = User::with('vendor')->find($request->user()->id);
            $promotion = $this->promotionService->requestPromotion($request->validated(), $user->vendor);

            return ShopittPlus::response(true, 'Promotion request submitted successfully', 201, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('REQUEST PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('REQUEST PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to submit promotion request', 500);
        }
    }

    /**
     * Get vendor's promotion requests
     */
    public function myPromotions(Request $request): JsonResponse
    {
        try {
            $user = User::with('vendor')->find($request->user()->id);
            $filters = [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'per_page' => $request->query('per_page', 15),
            ];

            $promotions = $this->promotionService->getVendorPromotions($user->vendor->id, $filters);

            $data = [
                'data' => PromotionResource::collection($promotions->items()),
                'next_cursor' => $promotions->nextCursor()?->encode(),
                'prev_cursor' => $promotions->previousCursor()?->encode(),
                'has_more' => $promotions->hasMorePages(),
                'per_page' => $promotions->perPage(),
            ];
            return ShopittPlus::response(true, 'Promotion requests retrieved successfully', 200, $data);
        } catch (Exception $e) {
            Log::error('GET VENDOR PROMOTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve promotion requests', 500);
        }
    }

    /**
     * Cancel promotion request
     */
    public function cancelRequest(Request $request, string $id): JsonResponse
    {
        try {
            $vendorId = $request->user()->id;
            $this->promotionService->cancelPromotionRequest($id, $vendorId);

            return ShopittPlus::response(true, 'Promotion request cancelled successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('CANCEL PROMOTION REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CANCEL PROMOTION REQUEST: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to cancel promotion request', 500);
        }
    }
}
