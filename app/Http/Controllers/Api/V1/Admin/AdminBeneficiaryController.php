<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\TransactX;
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
            return TransactX::response(true, 'Beneficiaries retrieved successfully', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('ADMIN GET BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve beneficiaries', 500);
        }
    }

    /**
     * Get a single beneficiary
     */
    public function show(string $id): JsonResponse
    {
        try {
            $beneficiary = $this->adminBeneficiaryService->getBeneficiary($id);
            return TransactX::response(true, 'Beneficiary retrieved successfully', 200, $beneficiary);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Beneficiary not found') {
                return TransactX::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET BENEFICIARY: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve beneficiary', 500);
        }
    }

    /**
     * Get all beneficiaries for a specific user
     */
    public function userBeneficiaries(Request $request, string $userId): JsonResponse
    {
        try {
            $beneficiaries = $this->adminBeneficiaryService->getUserBeneficiaries($userId, $request->all());
            return TransactX::response(true, 'User beneficiaries retrieved successfully', 200, $beneficiaries);
        } catch (Exception $e) {
            Log::error('ADMIN GET USER BENEFICIARIES: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user beneficiaries', 500);
        }
    }
}