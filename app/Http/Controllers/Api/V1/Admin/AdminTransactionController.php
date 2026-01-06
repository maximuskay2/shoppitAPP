<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Transaction\Services\Admin\AdminTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminTransactionController extends Controller
{
    /**
     * Create a new AdminTransactionController instance.
     */
    public function __construct(
        protected AdminTransactionService $adminTransactionService,
    ) {}

    /**
     * Get all transactions with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $transactions = $this->adminTransactionService->getTransactions($request->all());
            return ShopittPlus::response(true, 'Transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN GET TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve transactions', 500);
        }
    }

    /**
     * Get a single transaction
     */
    public function show(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminTransactionService->getTransaction($id);
            return ShopittPlus::response(true, 'Transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Transaction not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET TRANSACTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve transaction', 500);
        }
    }

    /**
     * Get transaction statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $stats = $this->adminTransactionService->getTransactionStats();

            return ShopittPlus::response(true, 'Transaction statistics retrieved successfully', 200, $stats);
        } catch (Exception $e) {
            Log::error('GET TRANSACTION STATS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve transaction statistics', 500);
        }
    }

    /**
     * Get transaction reports
     */
    public function reports(Request $request): JsonResponse
    {
        try {
            $reports = $this->adminTransactionService->getTransactionReports($request);

            return ShopittPlus::response(true, 'Transaction reports retrieved successfully', 200, $reports);
        } catch (Exception $e) {
            Log::error('GET TRANSACTION REPORTS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve transaction reports', 500);
        }
    }
}