<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\Commerce\CreatePromotionRequest;
use App\Http\Requests\Api\Admin\Commerce\UpdatePromotionRequest;
use App\Http\Resources\Commerce\PromotionResource;
use App\Modules\Commerce\Services\Admin\PromotionManagementService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AdminPromotionController extends Controller
{
    public function __construct(private readonly PromotionManagementService $promotionManagementService) {}

    /**
     * Get promotion statistics
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->promotionManagementService->getStats();

            return ShopittPlus::response(true, 'Promotion statistics retrieved successfully', 200, $stats);
        } catch (Exception $e) {
            Log::error('GET PROMOTION STATS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve promotion statistics', 500);
        }
    }

    /**
     * List all promotions with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'active' => $request->query('active'),
                'scheduled' => $request->query('scheduled'),
                'expired' => $request->query('expired'),
                'vendor_id' => $request->query('vendor_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'per_page' => $request->query('per_page', 15),
            ];

            $promotions = $this->promotionManagementService->listPromotions($filters);

            return ShopittPlus::response(true, 'Promotions retrieved successfully', 200, PromotionResource::collection($promotions)->response()->getData());
        } catch (Exception $e) {
            Log::error('LIST PROMOTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve promotions', 500);
        }
    }

    /**
     * Get a single promotion
     */
    public function show(string $id): JsonResponse
    {
        try {
            $promotion = $this->promotionManagementService->getPromotion($id);

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
     * Create a new promotion
     */
    public function store(CreatePromotionRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            
            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = $request->file('banner_image');
            }

            $promotion = $this->promotionManagementService->createPromotion($data);

            return ShopittPlus::response(true, 'Promotion created successfully', 201, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('CREATE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('CREATE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to create promotion', 500);
        }
    }

    /**
     * Update a promotion
     */
    public function update(UpdatePromotionRequest $request, string $id): JsonResponse
    {
        try {
            $data = $request->validated();
            
            if ($request->hasFile('banner_image')) {
                $data['banner_image'] = $request->file('banner_image');
            }

            $promotion = $this->promotionManagementService->updatePromotion($id, $data);

            return ShopittPlus::response(true, 'Promotion updated successfully', 200, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('UPDATE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('UPDATE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update promotion', 500);
        }
    }

    /**
     * Delete a promotion
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->promotionManagementService->deletePromotion($id);

            return ShopittPlus::response(true, 'Promotion deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            Log::error('DELETE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 404);
        } catch (Exception $e) {
            Log::error('DELETE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete promotion', 500);
        }
    }

    /**
     * Approve a promotion request
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        try {
            $adminId = $request->user('admin-api')->id;
            $promotion = $this->promotionManagementService->approvePromotion($id, $adminId);

            return ShopittPlus::response(true, 'Promotion approved successfully', 200, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('APPROVE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('APPROVE PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to approve promotion', 500);
        }
    }

    /**
     * Reject a promotion request
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        try {
            $adminId = $request->user('admin-api')->id;
            $promotion = $this->promotionManagementService->rejectPromotion($id, $adminId);

            return ShopittPlus::response(true, 'Promotion rejected successfully', 200, new PromotionResource($promotion));
        } catch (InvalidArgumentException $e) {
            Log::error('REJECT PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('REJECT PROMOTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to reject promotion', 500);
        }
    }
}
