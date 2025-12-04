<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreReviewRequest;
use App\Http\Resources\Commerce\ReviewResource;
use App\Modules\Commerce\Models\Review;
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
    public function __construct()
    {
        // Constructor can be used for dependency injection if needed
    }

    /**
     * Store a new review for a vendor
     */
    public function store(StoreReviewRequest $request, Vendor $vendor): JsonResponse
    {
        try {
            DB::beginTransaction();

            $validatedData = $request->validated();
            $user = Auth::user();

            // Check if user has already reviewed this vendor
            $existingReview = Review::where('user_id', $user->id)
                ->where('reviewable_type', Vendor::class)
                ->where('reviewable_id', $vendor->id)
                ->first();

            if ($existingReview) {
                throw new InvalidArgumentException('You have already reviewed this vendor.');
            }

            $review = Review::create([
                'user_id' => $user->id,
                'reviewable_type' => Vendor::class,
                'reviewable_id' => $vendor->id,
                'rating' => $validatedData['rating'],
                'comment' => $validatedData['comment'] ?? null,
            ]);

            DB::commit();
            return ShopittPlus::response(true, 'Review submitted successfully', 201, new ReviewResource($review));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('STORE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('STORE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to submit review', 500);
        }
    }

    /**
     * Get reviews for a specific vendor
     */
    public function index(Request $request, Vendor $vendor): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $reviews = $vendor->reviews()
                ->with('user:id,name,avatar')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return ShopittPlus::response(true, 'Reviews retrieved successfully', 200, ReviewResource::collection($reviews));
        } catch (Exception $e) {
            Log::error('GET REVIEWS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve reviews', 500);
        }
    }

    /**
     * Update an existing review
     */
    public function update(StoreReviewRequest $request, Review $review): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Check if the review belongs to the authenticated user
            if ($review->user_id !== $user->id) {
                throw new InvalidArgumentException('You can only update your own reviews.');
            }

            $validatedData = $request->validated();

            $review->update([
                'rating' => $validatedData['rating'],
                'comment' => $validatedData['comment'] ?? null,
            ]);

            DB::commit();
            return ShopittPlus::response(true, 'Review updated successfully', 200, new ReviewResource($review));
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('UPDATE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('UPDATE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to update review', 500);
        }
    }

    /**
     * Delete a review
     */
    public function destroy(Review $review): JsonResponse
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();

            // Check if the review belongs to the authenticated user
            if ($review->user_id !== $user->id) {
                throw new InvalidArgumentException('You can only delete your own reviews.');
            }

            $review->delete();

            DB::commit();
            return ShopittPlus::response(true, 'Review deleted successfully', 200);
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            Log::error('DELETE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, $e->getMessage(), 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('DELETE REVIEW: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete review', 500);
        }
    }
}