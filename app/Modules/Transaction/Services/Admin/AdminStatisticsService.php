<?php

namespace App\Services\Admin;

use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdminStatisticsService
{
    /**
     * Get admin dashboard statistics
     */
    public function getStatistics(): array
    {
        try {
            // Total revenue: sum of all successful fee transactions
            $totalRevenue = Transaction::where('status', 'SUCCESSFUL')
                ->whereNotNull('principal_transaction_id')
                ->sum(DB::raw('CAST(amount AS DECIMAL(15,2))'));

            // Total transactions: count of principal transactions
            $totalTransactions = Transaction::whereNull('principal_transaction_id')->count();

            // Active users: users where is_active = true
            $activeUsers = User::where('is_active', true)->count();

            // Pending approvals: users with incomplete KYC or KYB
            $pendingApprovals = User::where(function ($query) {
                $query->where('kyc_status', '!=', 'SUCCESSFUL')
                      ->orWhere('kyb_status', '!=', 'SUCCESSFUL')
                      ->orWhereNull('kyc_status')
                      ->orWhereNull('kyb_status');
            })->count();

            return [
                'totalRevenue' => (float) $totalRevenue,
                'totalTransactions' => $totalTransactions,
                'activeUsers' => $activeUsers,
                'pendingApprovals' => $pendingApprovals,
            ];
        } catch (Exception $e) {
            Log::error('ADMIN STATISTICS SERVICE - GET STATISTICS: Error Encountered: ' . $e->getMessage());
            throw $e;
        }
    }
}