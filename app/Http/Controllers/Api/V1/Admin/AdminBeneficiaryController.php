<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminBeneficiaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminBeneficiaryController extends Controller
{
    /**
     * Create a new AdminBeneficiaryController instance.
     */
    public function __construct(
        protected AdminBeneficiaryService $adminBeneficiaryService,
    ) {}

    /**
     * Get all beneficiaries with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $beneficiaries = $this->adminBeneficiaryService->getBeneficiaries($request->all());
            return ShopittPlus::response(true, 'Beneficiaries retrieved successfully', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('ADMIN GET BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve beneficiaries', 500);
        }
    }

    /**
     * Get a single beneficiary
     */
    public function show(string $id): JsonResponse
    {
        try {
            $beneficiary = $this->adminBeneficiaryService->getBeneficiary($id);
            return ShopittPlus::response(true, 'Beneficiary retrieved successfully', 200, $beneficiary);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Beneficiary not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve beneficiary', 500);
        }
    }

    /**
     * Get all beneficiaries for a specific user
     */
    public function userBeneficiaries(Request $request, string $userId): JsonResponse
    {
        try {
            $beneficiaries = $this->adminBeneficiaryService->getUserBeneficiaries($userId, $request->all());
            return ShopittPlus::response(true, 'User beneficiaries retrieved successfully', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('ADMIN GET USER BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve user beneficiaries', 500);
        }
    }
}