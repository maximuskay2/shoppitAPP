<?php

namespace App\Http\Controllers\Commerce;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\PromotionResource;
use App\Modules\Commerce\Services\PromotionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    public function __construct(private readonly PromotionService $promotionService) {}

    /**
     * Get active promotions for storefront
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $promotions = $this->promotionService->getActivePromotions();

            return ShopittPlus::response(true, 'Active promotions retrieved successfully', 200, PromotionResource::collection($promotions));
        } catch (Exception $e) {
            Log::error('GET ACTIVE PROMOTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve active promotions', 500);
        }
    }
}
