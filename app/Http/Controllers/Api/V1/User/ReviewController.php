<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\Commerce\StoreReviewRequest;
use App\Http\Resources\Commerce\ReviewResource;
use App\Modules\Commerce\Models\Review;
use App\Modules\Commerce\Services\ReviewService;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    /**
     * Get reviews for a specific vendor
     */
    public function index(Request $request, string $id): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);

            $reviews = $this->reviewService->index($id, $perPage);
            return ShopittPlus::response(true, 'Reviews retrieved successfully', 200, ReviewResource::collection($reviews));
        } catch (Exception $e) {
            Log::error('GET REVIEWS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve reviews', 500);
        }
    }

    /**
     * Store a new review for a vendor
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            $user = User::find(Auth::id());
            $review = $this->reviewService->store($user, $request->validated());
            return ShopittPlus::response(true, 'Review submitted successfully', 201, new ReviewResource($review));
        } catch (InvalidArgumentException $e) {
            Log::error('STORE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            Log::error('STORE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to submit review', 500);
        }
    }
}