<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminTransactionService;
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
            return TransactX::response(true, 'Transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN GET TRANSACTIONS: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve transactions', 500);
        }
    }

    /**
     * Get a single transaction
     */
    public function show(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminTransactionService->getTransaction($id);
            return TransactX::response(true, 'Transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Transaction not found') {
                return TransactX::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET TRANSACTION: Error Encountered: ' . $e->getMessage());
            return TransactX::response(false, 'Failed to retrieve transaction', 500);
        }
    }
}