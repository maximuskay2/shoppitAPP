<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminVirtualBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminVirtualBankAccountController extends Controller
{
    /**
     * Create a new AdminVirtualBankAccountController instance.
     */
    public function __construct(
        protected AdminVirtualBankAccountService $adminVirtualBankAccountService,
    ) {}

    /**
     * Get all virtual bank accounts with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $virtualBankAccounts = $this->adminVirtualBankAccountService->getVirtualBankAccounts($request->all());
            return TransactX::response(true, 'Virtual bank accounts retrieved successfully', 200, $virtualBankAccounts);
        } catch (Exception $e) {
            Log::error('ADMIN GET VIRTUAL BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve virtual bank accounts', 500);
        }
    }

    /**
     * Get a single virtual bank account
     */
    public function show(string $id): JsonResponse
    {
        try {
            $virtualBankAccount = $this->adminVirtualBankAccountService->getVirtualBankAccount($id);
            return TransactX::response(true, 'Virtual bank account retrieved successfully', 200, $virtualBankAccount);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Virtual bank account not found') {
                return TransactX::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET VIRTUAL BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve virtual bank account', 500);
        }
    }

    /**
     * Get all virtual bank accounts for a specific user
     */
    public function userVirtualBankAccounts(string $userId): JsonResponse
    {
        try {
            $virtualBankAccounts = $this->adminVirtualBankAccountService->getUserVirtualBankAccounts($userId);
            return TransactX::response(true, 'User virtual bank accounts retrieved successfully', 200, $virtualBankAccounts);
        } catch (Exception $e) {
            Log::error('ADMIN GET USER VIRTUAL BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve user virtual bank accounts', 500);
        }
    }
}