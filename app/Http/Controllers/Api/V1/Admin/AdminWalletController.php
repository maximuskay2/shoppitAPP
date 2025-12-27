<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminWalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminWalletController extends Controller
{
    /**
     * Create a new AdminWalletController instance.
     */
    public function __construct(
        protected AdminWalletService $adminWalletService,
    ) {}

    /**
     * Get all wallets with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $wallets = $this->adminWalletService->getWallets($request->all());
            return ShopittPlus::response(true, 'Wallets retrieved successfully', 200, $wallets);
        } catch (Exception $e) {
            Log::error('ADMIN GET WALLETS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve wallets', 500);
        }
    }

    /**
     * Get a single wallet
     */
    public function show(string $id): JsonResponse
    {
        try {
            $wallet = $this->adminWalletService->getWallet($id);
            return ShopittPlus::response(true, 'Wallet retrieved successfully', 200, $wallet);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Wallet not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET WALLET: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve wallet', 500);
        }
    }

    /**
     * Delete a wallet
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $this->adminWalletService->deleteWallet($id);
            return ShopittPlus::response(true, 'Wallet deleted successfully', 200);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN DELETE WALLET: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to delete wallet', 500);
        }
    }
}