<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminLinkedBankAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminLinkedBankAccountController extends Controller
{
    /**
     * Create a new AdminLinkedBankAccountController instance.
     */
    public function __construct(
        protected AdminLinkedBankAccountService $adminLinkedBankAccountService,
    ) {}

    /**
     * Get all linked bank accounts with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $linkedBankAccounts = $this->adminLinkedBankAccountService->getLinkedBankAccounts($request->all());
            return ShopittPlus::response(true, 'Linked bank accounts retrieved successfully', 200, $linkedBankAccounts);
        } catch (Exception $e) {
            Log::error('ADMIN GET LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve linked bank accounts', 500);
        }
    }

    /**
     * Get a single linked bank account
     */
    public function show(string $id): JsonResponse
    {
        try {
            $linkedBankAccount = $this->adminLinkedBankAccountService->getLinkedBankAccount($id);
            return ShopittPlus::response(true, 'Linked bank account retrieved successfully', 200, $linkedBankAccount);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Linked bank account not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET LINKED BANK ACCOUNT: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve linked bank account', 500);
        }
    }

    /**
     * Get all linked bank accounts for a specific user
     */
    public function userLinkedBankAccounts(Request $request, string $userId): JsonResponse
    {
        try {
            $linkedBankAccounts = $this->adminLinkedBankAccountService->getUserLinkedBankAccounts($userId, $request->all());
            return ShopittPlus::response(true, 'User linked bank accounts retrieved successfully', 200, $linkedBankAccounts);
        } catch (Exception $e) {
            Log::error('ADMIN GET USER LINKED BANK ACCOUNTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve user linked bank accounts', 500);
        }
    }
}