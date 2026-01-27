<?php

namespace App\Modules\Commerce\Services;

use App\Modules\Commerce\Models\Promotion;
use App\Modules\User\Models\Vendor;
use App\Modules\User\Services\CloudinaryService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PromotionService
{
    public function __construct(private readonly CloudinaryService $cloudinaryService) {}
    
    /**
     * List available promotions for vendors/customers
     */
    public function listAvailablePromotions(array $filters = []): mixed
    {
        try {
            $query = Promotion::with(['vendor'])
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc');

            // Search filter
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            // Filter by active/scheduled/expired
            if (isset($filters['type']) && !empty($filters['type'])) {
                switch ($filters['type']) {
                    case 'active':
                        $query->active();
                        break;
                    case 'scheduled':
                        $query->scheduled();
                        break;
                    case 'expired':
                        $query->expired();
                        break;
                }
            }

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->cursorPaginate($perPage);
        } catch (Exception $e) {
            Log::error('PROMOTION SERVICE - LIST AVAILABLE PROMOTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get active promotions for storefront (customers)
     */
    public function getActivePromotions(): mixed
    {
        try {
            return Promotion::with(['vendor'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (Exception $e) {
            Log::error('PROMOTION SERVICE - GET ACTIVE PROMOTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Request a promotion (vendor)
     */
    public function requestPromotion(array $data, Vendor $vendor): Promotion
    {
        try {
            DB::beginTransaction();

            $bannerImageUrl = null;
            if (isset($data['banner_image'])) {
                $bannerImageUrl = $this->cloudinaryService->uploadBlogImage($data['banner_image'], 'promotion-banners');
            }

            $promotion = Promotion::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'vendor_id' => $vendor->id,
                'reason' => $data['reason'] ?? null,
                'status' => 'pending',
                'is_active' => true,
                'banner_image' => $bannerImageUrl,
            ]);

            DB::commit();

            return $promotion->fresh(['vendor']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PROMOTION SERVICE - REQUEST PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get vendor's promotion requests
     */
    public function getVendorPromotions(string $vendorId, array $filters = []): mixed
    {
        try {
            $query = Promotion::with(['approvedBy'])
                ->where('vendor_id', $vendorId)
                ->orderBy('created_at', 'desc');

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

            // Paginate results
            $perPage = $filters['per_page'] ?? 15;
            return $query->cursorPaginate($perPage);
        } catch (Exception $e) {
            Log::error('PROMOTION SERVICE - GET VENDOR PROMOTIONS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel promotion request (vendor)
     */
    public function cancelPromotionRequest(string $id, string $vendorId): void
    {
        try {
            $promotion = Promotion::where('id', $id)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$promotion) {
                throw new InvalidArgumentException('Promotion request not found');
            }

            if ($promotion->status !== 'pending') {
                throw new InvalidArgumentException('Only pending promotion requests can be cancelled');
            }

            $promotion->forceDelete();
        } catch (Exception $e) {
            Log::error('PROMOTION SERVICE - CANCEL PROMOTION REQUEST: Error Encountered: ' . $e->getMessage());
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
            Log::error('PROMOTION SERVICE - GET PROMOTION: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}
