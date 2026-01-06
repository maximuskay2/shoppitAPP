<?php

namespace App\Modules\Commerce\Services\Admin;

use App\Modules\Commerce\Models\Promotion;
use App\Modules\User\Services\CloudinaryService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PromotionManagementService
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}

    /**
     * Get promotion statistics
     */
    public function getStats(): array
    {
        try {
            $totalPromotions = Promotion::count();
            $activePromotions = Promotion::active()->count();
            $scheduledPromotions = Promotion::scheduled()->count();
            $expiredPromotions = Promotion::expired()->count();

            return [
                'total_promotions' => $totalPromotions,
                'active_promotions' => $activePromotions,
                'scheduled_promotions' => $scheduledPromotions,
                'expired_promotions' => $expiredPromotions,
            ];
        } catch (Exception $e) {
            Log::error('PROMOTION MANAGEMENT SERVICE - GET STATS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * List promotions with advanced filtering
     */
    public function listPromotions(array $filters = []): mixed
    {
        try {
            $query = Promotion::with(['vendor', 'approvedBy'])->orderBy('created_at', 'desc');

            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            // Active filter
            if (isset($filters['active']) && $filters['active'] !== '') {
                if ($filters['active'] === 'true' || $filters['active'] === '1') {
                    $query->active();
                }
            }

            // Scheduled filter
            if (isset($filters['scheduled']) && $filters['scheduled'] !== '') {
                if ($filters['scheduled'] === 'true' || $filters['scheduled'] === '1') {
                    $query->scheduled();
                }
            }

            // Expired filter
            if (isset($filters['expired']) && $filters['expired'] !== '') {
                if ($filters['expired'] === 'true' || $filters['expired'] === '1') {
                    $query->expired();
                }
            }

            // Vendor filter
            if (isset($filters['vendor_id']) && !empty($filters['vendor_id'])) {
                $query->where('vendor_id', $filters['vendor_id']);
            }

            // Date range filter
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $query->whereDate('start_date', '>=', $filters['start_date']);
            }

            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $query->whereDate('end_date', '<=', $filters['end_date']);
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->paginate($perPage);
        } catch (Exception $e) {
            Log::error('PROMOTION MANAGEMENT SERVICE - LIST PROMOTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a single promotion
     */
    public function getPromotion(string $id): Promotion
    {
        try {
            $promotion = Promotion::with(['vendor', 'approvedBy'])->find($id);

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion not found');
            }

            return $promotion;
        } catch (Exception $e) {
            Log::error('PROMOTION MANAGEMENT SERVICE - GET PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a new promotion
     */
    public function createPromotion(array $data): Promotion
    {
        try {
            DB::beginTransaction();

            // Upload banner image to Cloudinary if provided
            $bannerImageUrl = null;
            if (isset($data['banner_image'])) {
                $bannerImageUrl = $this->cloudinaryService->uploadBlogImage($data['banner_image'], 'promotion-banners');
            }

            // If vendor_id is provided, it's a vendor request (pending), otherwise it's admin-created (approved)
            $status = isset($data['vendor_id']) ? 'pending' : 'approved';

            $promotion = Promotion::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'banner_image' => $bannerImageUrl,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => $data['is_active'] ?? true,
                'vendor_id' => $data['vendor_id'] ?? null,
                'reason' => $data['reason'] ?? null,
                'status' => $status,
            ]);

            DB::commit();

            return $promotion->fresh(['vendor', 'approvedBy']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PROMOTION MANAGEMENT SERVICE - CREATE PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update a promotion
     */
    public function updatePromotion(string $id, array $data): Promotion
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::find($id);

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion not found');
            }

            $updateData = [
                'title' => $data['title'] ?? $promotion->title,
                'description' => $data['description'] ?? $promotion->description,
                'discount_type' => $data['discount_type'] ?? $promotion->discount_type,
                'discount_value' => $data['discount_value'] ?? $promotion->discount_value,
                'start_date' => $data['start_date'] ?? $promotion->start_date,
                'end_date' => $data['end_date'] ?? $promotion->end_date,
                'is_active' => $data['is_active'] ?? $promotion->is_active,
            ];

            // Upload new banner image if provided
            if (isset($data['banner_image'])) {
                $updateData['banner_image'] = $this->cloudinaryService->uploadBlogImage($data['banner_image'], 'promotion-banners');
            }

            $promotion->update($updateData);

            DB::commit();

            return $promotion->fresh(['vendor', 'approvedBy']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PROMOTION MANAGEMENT SERVICE - UPDATE PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete a promotion
     */
    public function deletePromotion(string $id): void
    {
        try {
            $promotion = Promotion::find($id);

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion not found');
            }

            $promotion->delete();
        } catch (Exception $e) {
            Log::error('PROMOTION MANAGEMENT SERVICE - DELETE PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Approve a promotion request
     */
    public function approvePromotion(string $id, string $adminId): Promotion
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::find($id);

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion not found');
            }

            if ($promotion->status !== 'pending') {
                throw new InvalidArgumentException('Only pending promotions can be approved');
            }

            $promotion->update([
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            DB::commit();

            return $promotion->fresh(['vendor', 'approvedBy']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PROMOTION MANAGEMENT SERVICE - APPROVE PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reject a promotion request
     */
    public function rejectPromotion(string $id, string $adminId): Promotion
    {
        try {
            DB::beginTransaction();

            $promotion = Promotion::find($id);

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion not found');
            }

            if ($promotion->status !== 'pending') {
                throw new InvalidArgumentException('Only pending promotions can be rejected');
            }

            $promotion->update([
                'status' => 'rejected',
                'approved_by' => $adminId,
                'approved_at' => now(),
            ]);

            DB::commit();

            return $promotion->fresh(['vendor', 'approvedBy']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PROMOTION MANAGEMENT SERVICE - REJECT PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
