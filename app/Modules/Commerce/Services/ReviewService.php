<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\Review;
use App\Modules\User\Models\User;
use App\Modules\User\Models\Vendor;

class ReviewService
{
    public function index(string $reviewableId, int $perPage = 15)
    {
        // Logic to retrieve reviews for a specific reviewable entity (vendor or product)
        return Review::where('reviewable_id', $reviewableId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function store(User $user, array $data): Review
    {
        // Logic to store the review based on type and reviewable_id
        $review = new Review();
        $review->user_id = $user->id;
        $review->rating = $data['rating'];
        $review->comment = $data['comment'] ?? null;
        $review->reviewable_type = match ($data['reviewable_type']) {
            'vendor' => Vendor::class,
            'product' => Product::class,
            default => throw new \InvalidArgumentException('Invalid review type'),
        };
        $review->reviewable_id = $data['reviewable_id'];
        $review->save();

        return $review;
    }
}