<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Services\Admin\AdminServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Log;

class AdminServiceManagementController extends Controller
{
    /**
     * Create a new AdminServiceManagementController instance.
     */
    public function __construct(
        protected AdminServiceManagementService $adminServiceManagementService,
    ) {}

    /**
     * Get airtime service statistics
     */
    public function airtimeStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->adminServiceManagementService->getAirtimeStatistics($request->all());
            return ShopittPlus::response(true, 'Airtime statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN AIRTIME STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve airtime statistics', 500);
        }
    }

    /**
     * Get airtime transactions with filters
     */
    public function airtimeIndex(Request $request): JsonResponse
    {
        try {
            $transactions = $this->adminServiceManagementService->getAirtimeTransactions($request->all());
            return ShopittPlus::response(true, 'Airtime transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN AIRTIME INDEX: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve airtime transactions', 500);
        }
    }

    /**
     * Get a single airtime transaction
     */
    public function airtimeShow(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminServiceManagementService->getAirtimeTransaction($id);
            return ShopittPlus::response(true, 'Airtime transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Airtime transaction not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET AIRTIME TRANSACTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve airtime transaction', 500);
        }
    }

    /**
     * Get all airtime networks
     */
    public function airtimeNetworks(): JsonResponse
    {
        try {
            $networks = $this->adminServiceManagementService->getAirtimeNetworks();
            return ShopittPlus::response(true, 'Airtime networks retrieved successfully', 200, $networks);
        } catch (Exception $e) {
            Log::error('ADMIN GET AIRTIME NETWORKS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve airtime networks', 500);
        }
    }

    /**
     * Get data service statistics
     */
    public function dataStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->adminServiceManagementService->getDataStatistics($request->all());
            return ShopittPlus::response(true, 'Data statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN DATA STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve data statistics', 500);
        }
    }

    /**
     * Get data transactions with filters
     */
    public function dataIndex(Request $request): JsonResponse
    {
        try {
            $transactions = $this->adminServiceManagementService->getDataTransactions($request->all());
            return ShopittPlus::response(true, 'Data transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN DATA INDEX: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve data transactions', 500);
        }
    }

    /**
     * Get a single data transaction
     */
    public function dataShow(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminServiceManagementService->getDataTransaction($id);
            return ShopittPlus::response(true, 'Data transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Data transaction not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET DATA TRANSACTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve data transaction', 500);
        }
    }

    /**
     * Get electricity service statistics
     */
    public function electricityStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->adminServiceManagementService->getElectricityStatistics($request->all());
            return ShopittPlus::response(true, 'Electricity statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN ELECTRICITY STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve electricity statistics', 500);
        }
    }

    /**
     * Get electricity transactions with filters
     */
    public function electricityIndex(Request $request): JsonResponse
    {
        try {
            $transactions = $this->adminServiceManagementService->getElectricityTransactions($request->all());
            return ShopittPlus::response(true, 'Electricity transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN ELECTRICITY INDEX: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve electricity transactions', 500);
        }
    }

    /**
     * Get a single electricity transaction
     */
    public function electricityShow(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminServiceManagementService->getElectricityTransaction($id);
            return ShopittPlus::response(true, 'Electricity transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'Electricity transaction not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET ELECTRICITY TRANSACTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve electricity transaction', 500);
        }
    }

    /**
     * Get TV service statistics
     */
    public function tvStatistics(Request $request): JsonResponse
    {
        try {
            $statistics = $this->adminServiceManagementService->getTVStatistics($request->all());
            return ShopittPlus::response(true, 'TV statistics retrieved successfully', 200, $statistics);
        } catch (Exception $e) {
            Log::error('ADMIN TV STATISTICS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve TV statistics', 500);
        }
    }

    /**
     * Get TV transactions with filters
     */
    public function tvIndex(Request $request): JsonResponse
    {
        try {
            $transactions = $this->adminServiceManagementService->getTVTransactions($request->all());
            return ShopittPlus::response(true, 'TV transactions retrieved successfully', 200, $transactions);
        } catch (Exception $e) {
            Log::error('ADMIN TV INDEX: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve TV transactions', 500);
        }
    }

    /**
     * Get a single TV transaction
     */
    public function tvShow(string $id): JsonResponse
    {
        try {
            $transaction = $this->adminServiceManagementService->getTVTransaction($id);
            return ShopittPlus::response(true, 'TV transaction retrieved successfully', 200, $transaction);
        } catch (Exception $e) {
            if ($e->getMessage() === 'TV transaction not found') {
                return ShopittPlus::response(false, $e->getMessage(), 404);
            }
            Log::error('ADMIN GET TV TRANSACTION: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve TV transaction', 500);
        }
    }

    /**
     * Get all TV providers
     */
    public function tvProviders(): JsonResponse
    {
        try {
            $providers = $this->adminServiceManagementService->getTVProviders();
            return ShopittPlus::response(true, 'TV providers retrieved successfully', 200, $providers);
        } catch (Exception $e) {
            Log::error('ADMIN GET TV PROVIDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve TV providers', 500);
        }
    }

    /**
     * Get all electricity providers
     */
    public function electricityProviders(): JsonResponse
    {
        try {
            $providers = $this->adminServiceManagementService->getElectricityProviders();
            return ShopittPlus::response(true, 'Electricity providers retrieved successfully', 200, $providers);
        } catch (Exception $e) {
            Log::error('ADMIN GET ELECTRICITY PROVIDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve electricity providers', 500);
        }
    }

    /**
     * Get all data service providers
     */
    public function dataProviders(): JsonResponse
    {
        try {
            $providers = $this->adminServiceManagementService->getDataProviders();
            return ShopittPlus::response(true, 'Data providers retrieved successfully', 200, $providers);
        } catch (Exception $e) {
            Log::error('ADMIN GET DATA PROVIDERS: Error Encountered: ' . $e->getMessage());
            return ShopittPlus::response(false, 'Failed to retrieve data providers', 500);
        }
    }
}