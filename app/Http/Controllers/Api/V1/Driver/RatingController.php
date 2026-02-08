<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Commerce\ReviewResource;
use App\Modules\Commerce\Models\Review;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    public function summary(): JsonResponse
    {
        try {
            $driver = User::with('driver')->find(Auth::id());

            if (!$driver || !$driver->driver) {
                return ShopittPlus::response(false, 'Driver profile not found.', 404);
            }

            $query = Review::where('reviewable_type', User::class)
                ->where('reviewable_id', $driver->id);

            $average = round((float) $query->avg('rating'), 2);
            $total = $query->count();
            $recent = $query->latest()->limit(20)->get();

            return ShopittPlus::response(true, 'Driver ratings retrieved successfully', 200, [
                'average_rating' => $average,
                'total_reviews' => $total,
                'reviews' => ReviewResource::collection($recent),
            ]);
        } catch (\Exception $e) {
            Log::error('DRIVER RATINGS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve ratings', 500);
        }
    }
}
